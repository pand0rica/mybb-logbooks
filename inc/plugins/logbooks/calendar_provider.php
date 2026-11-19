<?php
/**
 * Logbook – RPG Calendar provider (optional integration)
 *
 * Delivers the logged-in user's scenes to the rpgcalendar plugin through its
 * provider hooks. rpgcalendar never touches the Logbook tables; if rpgcalendar is
 * not installed these handlers are simply never called.
 *
 * Contract (see rpgcalendar README): fires once per month render, titles RAW
 * (the consumer escapes), over-delivery outside the month is ignored, malformed
 * entries are silently discarded.
 *
 * Which scenes are delivered for the viewer:
 *  - character account (as_uid > 0)        -> only the scenes of that account
 *  - main account (as_uid = 0) with alts   -> scenes of the main account AND all
 *                                             linked character accounts
 *  - without EnhancedAccountSwitcher       -> only the viewer's own scenes
 * Scene types: 0 (normal) and 3 (planned) are shown. AU (1) and unplayed (2)
 * scenes are not part of the in-game timeline and are skipped.
 */

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

/** Key of the virtual sub-calendar that carries all Logbook scenes. */
const LOGBOOK_CALENDAR_KEY = 'logbook-scenes';

/** Default colour of the scenes sub-calendar (users can override it). */
const LOGBOOK_CALENDAR_COLOR = '#2563eb';

/**
 * UIDs whose scenes the current viewer sees in the calendar.
 *
 * Unlike logbook_db_get_linked_user_ids() this includes the main account itself.
 *
 * @param int $uid Viewer uid
 * @return int[] Empty for guests
 */
function logbook_calendar_uids_for_viewer(int $uid): array
{
    global $db;

    if ($uid <= 0) {
        return [];
    }

    if (!logbook_is_accountswitcher_active() || !$db->field_exists('as_uid', 'users')) {
        return [$uid];
    }

    $row = $db->fetch_array($db->simple_select('users', 'as_uid', 'uid = ' . $uid));
    if (!$row || (int)$row['as_uid'] > 0) {
        // Character account: own scenes only
        return [$uid];
    }

    // Main account: itself plus every linked character
    $uids  = [$uid];
    $query = $db->simple_select('users', 'uid', 'as_uid = ' . $uid);
    while ($alt = $db->fetch_array($query)) {
        $uids[] = (int)$alt['uid'];
    }

    return array_values(array_unique($uids));
}

/**
 * Scenes of the given accounts that fall into one in-game month.
 *
 * One query per call. Scenes without a valid date, in unviewable forums, in
 * unapproved/deleted threads, or of type AU/unplayed are excluded.
 *
 * @param int   $year  In-game year
 * @param int   $month In-game month (1-12)
 * @param int[] $uids  Participant accounts
 * @return array[] Rows: id, tid, tag, time, ort, scene_type, title, subject
 */
function logbook_db_get_scenes_in_month(int $year, int $month, array $uids): array
{
    global $db;

    $uids = array_values(array_filter(array_map('intval', $uids)));
    if (!$uids || $month < 1 || $month > 12) {
        return [];
    }

    $where = 's.jahr = ' . $year . ' AND s.monat = ' . $month
        . ' AND s.tag BETWEEN 1 AND 31'
        . ' AND s.scene_type IN (0, 3)'
        . ' AND l.user_id IN (' . implode(',', $uids) . ')'
        . ' AND (s.tid = 0 OR t.visible = 1)';

    // Respect forum permissions of the viewer for threaded scenes
    $unviewable = function_exists('get_unviewable_forums') ? get_unviewable_forums(true) : '';
    if ($unviewable !== '' && $unviewable !== null) {
        $where .= ' AND (s.tid = 0 OR t.fid NOT IN (' . $unviewable . '))';
    }

    $query = $db->query("
        SELECT DISTINCT s.id, s.tid, s.tag, s.`time` AS scene_time, s.ort, s.scene_type, s.title, t.subject
        FROM " . TABLE_PREFIX . "scene s
        INNER JOIN " . TABLE_PREFIX . "logbook_entry e ON e.scene_id = s.id
        INNER JOIN " . TABLE_PREFIX . "logbook l ON l.id = e.logbook_id
        LEFT JOIN " . TABLE_PREFIX . "threads t ON t.tid = s.tid
        WHERE " . $where . "
        ORDER BY s.tag ASC, s.id ASC
    ");

    $rows = [];
    while ($row = $db->fetch_array($query)) {
        $rows[] = $row;
    }

    return $rows;
}

/**
 * Hook 'rpgcalendar_collect_events': adds the viewer's scenes of one month.
 *
 * @param array $args ['year' => int, 'month' => int, 'events' => &array]
 */
function logbook_rpgcalendar_collect(array &$args): void
{
    global $mybb, $lang;

    if (!isset($args['year'], $args['month']) || !isset($mybb->user['uid'])) {
        return;
    }

    $uids = logbook_calendar_uids_for_viewer((int)$mybb->user['uid']);
    if (!$uids) {
        return;
    }

    $year  = (int)$args['year'];
    $month = (int)$args['month'];

    $lang->load('logbook');

    foreach (logbook_db_get_scenes_in_month($year, $month, $uids) as $scene) {
        $title = (int)$scene['tid'] > 0 && (string)$scene['subject'] !== ''
            ? (string)$scene['subject']
            : (string)$scene['title'];
        if ($title === '') {
            continue;
        }

        $time = (int)$scene['scene_time'];
        if ($time >= 1 && $time <= 5) {
            $title .= ' (' . logbook_get_time($time) . ')';
        }

        $day     = (int)$scene['tag'];
        $planned = (int)$scene['scene_type'] === 3;

        $args['events'][] = [
            'id'            => 'scene-' . (int)$scene['id'],
            'source'        => 'scene',
            'title'         => $title,
            'url'           => (int)$scene['tid'] > 0
                ? 'showthread.php?tid=' . (int)$scene['tid']
                : 'logbook.php',
            'start'         => ['y' => $year, 'm' => $month, 'd' => $day],
            'end'           => ['y' => $year, 'm' => $month, 'd' => $day],
            'important'     => false,
            'badge'         => $planned ? 'scene-planned' : 'scene',
            'calendar_key'  => LOGBOOK_CALENDAR_KEY,
            'default_color' => LOGBOOK_CALENDAR_COLOR,
        ];
    }
}

/**
 * Hook 'rpgcalendar_collect_calendars': exposes the scenes sub-calendar so users
 * can colour/hide it on rpgcalendar's "My calendars" page.
 *
 * @param array $args ['calendars' => &array]
 */
function logbook_rpgcalendar_calendars(array &$args): void
{
    global $lang, $mybb;

    if (!isset($mybb->user['uid']) || (int)$mybb->user['uid'] <= 0) {
        return;
    }

    $lang->load('logbook');

    $args['calendars'][] = [
        'key'           => LOGBOOK_CALENDAR_KEY,
        'name'          => $lang->logbook_calendar_name,
        'default_color' => LOGBOOK_CALENDAR_COLOR,
    ];
}
