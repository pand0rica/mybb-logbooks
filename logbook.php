<?php
/**
 * Logbook Frontend-Seite
 *
 * Wird in das MyBB-Root-Verzeichnis kopiert und stellt das Benutzer-Interface
 * für das Logbook-Plugin bereit.
 *
 * @package   Logbook
 * @version   2.3.0
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'logbook.php');

require_once "./global.php";
require_once MYBB_ROOT . "inc/functions_post.php";
require_once MYBB_ROOT . "inc/functions_user.php";
// logbook_*()-Funktionen sind über inc/plugins/logbook.php bereits durch global.php geladen.

$lang->load('logbook');

// Nur für eingeloggte Benutzer mit Logbook-Berechtigung
if (empty($mybb->user['uid']) || (int)$mybb->user['uid'] <= 0 || !logbook_user_can_use()) {
    redirect("index.php");
    exit();
}

add_breadcrumb($lang->logbook_breadcrumb_cp, "logbook.php");

$action = $mybb->get_input('action');

switch ($action) {
    case 'anleitung':
        add_breadcrumb($lang->logbook_breadcrumb_guide);
        break;
    case 'config':
        add_breadcrumb($lang->logbook_breadcrumb_config);
        break;
    case 'scene_add':
        add_breadcrumb($lang->logbook_breadcrumb_add);
        break;
    case 'usercp_view':
        add_breadcrumb($lang->logbook_breadcrumb_view);
        break;
    case 'popup_view':
        add_breadcrumb($lang->logbook_breadcrumb_popup);
        break;
    case 'my_scenes':
        add_breadcrumb($lang->logbook_breadcrumb_my_scenes);
        break;
}

// Logbook für neuen Benutzer anlegen, falls noch keines existiert
logbook_ensure_logbook((int)$mybb->user['uid']);

usercp_menu();

// Make post key available as a simple variable for all form templates.
$post_key = $mybb->post_code;

// =============================================================================
// Startseite
// =============================================================================

if ($action === '') {
    $userId             = (int)$mybb->user['uid'];
    $sceneCount         = logbook_db_get_scene_count($userId);
    $sceneActiveCount   = logbook_db_get_scene_unfinished_count($userId);
    $sceneFinishedCount = logbook_db_get_scene_finished_count($userId);

    eval("\$logbook = \"" . $templates->get("logbook") . "\";");
    output_page($logbook);

// =============================================================================
// Logbook ansehen und bearbeiten
// =============================================================================
} elseif ($action === 'usercp_view') {
    $userId         = $mybb->get_input('userId', MyBB::INPUT_INT) ?: (int)$mybb->user['uid'];
    $viewing_own    = $userId === (int)$mybb->user['uid'];
    $scenes         = logbook_db_get_complete_logbook($userId);
    $usercp_viewbit = '';

    foreach ($scenes as $scene) {
        $scene_id = (int)$scene['scene_id'];

        // Private unplayed/planned scenes are only visible to the owner
        $scene_type_val = (int)($scene['scene_type'] ?? 0);
        if (!$viewing_own && $scene_type_val >= 2 && (int)($scene['visibility'] ?? 1) === 0) {
            continue;
        }

        $edit   = '';
        $delete = '';
        $status = '';

        if ($viewing_own && (int)$mybb->user['uid'] > 0) {
            if ((int)$scene['finished'] === 0) {
                eval("\$status = \"" . $templates->get("logbook_scene_unfinished_edit") . "\";");
            } else {
                eval("\$status = \"" . $templates->get("logbook_scene_finished_edit") . "\";");
            }
            $edit   = "<form method='get' action='logbook.php' class='lb-inline-form'>"
                    . "<input type='hidden' name='action' value='scene_edit' />"
                    . "<input type='hidden' name='sceneId' value='" . $scene_id . "' />"
                    . "<button type='submit' class='button lb-action'>" . $lang->logbook_action_edit . "</button>"
                    . "</form>";
            $delete = "<form method='post' action='logbook.php' class='lb-inline-form'>"
                    . "<input type='hidden' name='action' value='delete_entry' />"
                    . "<input type='hidden' name='entryId' value='" . (int)$scene['eid'] . "' />"
                    . "<input type='hidden' name='my_post_key' value='" . $mybb->post_code . "' />"
                    . "<button type='submit' class='button lb-action'>" . $lang->logbook_action_delete . "</button>"
                    . "</form>";
        } else {
            if ((int)$scene['finished'] === 0) {
                eval("\$status = \"" . $templates->get("logbook_scene_unfinished_edit_guest") . "\";");
            } else {
                eval("\$status = \"" . $templates->get("logbook_scene_finished_edit_guest") . "\";");
            }
        }

        $tag        = (int)$scene['tag'];
        $monat      = logbook_get_month((int)$scene['monat']);
        $jahr       = (int)$scene['jahr'];
        $ort        = htmlspecialchars_uni($scene['ort']);
        $zeit       = logbook_get_time((int)$scene['time']);
        $teilnehmer = logbook_get_participant_string((int)$scene['scene_id']);
        $title      = logbook_get_thread_link((int)$scene['tid'], (string)($scene['title'] ?? ''));
        $free_order_badge = !empty($scene['free_order'])
            ? '<span class="lb-free-order">' . $lang->logbook_no_fixed_order . '</span>'
            : '';
        $scene_type_badge = logbook_get_scene_type_badge((int)($scene['scene_type'] ?? 0));
        $beschreibung = htmlspecialchars_uni((string)($scene['text'] ?? ''));

        eval("\$usercp_viewbit .= \"" . $templates->get("logbook_usercp_viewbit") . "\";");
    }

    eval("\$logbook_usercp_view = \"" . $templates->get("logbook_usercp_view") . "\";");
    output_page($logbook_usercp_view);

// =============================================================================
// Logbook konfigurieren
// =============================================================================
} elseif ($action === 'config') {
    $logbook           = logbook_db_get_logbook((int)$mybb->user['uid']);
    $templateIdOptions = '';
    $description       = '';
    $savedNotice       = $mybb->get_input('saved', MyBB::INPUT_INT) === 1
        ? '<div class="logbook-notice success">' . $lang->logbook_success_saved . '</div>'
        : '';

    $acp_reminder_days    = (int)($mybb->settings['logbook_reminder_days'] ?? 0);
    $reminder_days_hint   = $lang->sprintf($lang->logbook_form_reminder_days_hint, $acp_reminder_days);

    if ($logbook) {
        $description   = htmlspecialchars_uni($logbook['description']);
        $reminder_days = (int)($logbook['reminder_days'] ?? 0);

        foreach (logbook_db_get_popup_template_ids() as $id) {
            $selected           = ($id == $logbook['template_id']) ? ' selected' : '';
            $templateIdOptions .= "<option value='" . htmlspecialchars_uni($id) . "'" . $selected . ">"
                                   . htmlspecialchars_uni($id) . "</option>\n";
        }
    }

    eval("\$page = \"" . $templates->get("logbook_config") . "\";");
    output_page($page);

// =============================================================================
// Konfiguration speichern
// =============================================================================
} elseif ($action === 'save_config') {
    verify_post_check($mybb->get_input('my_post_key'));

    logbook_db_logbook_update([
        'template_id'   => $mybb->get_input('template_id', MyBB::INPUT_INT),
        'description'   => $mybb->get_input('description'),
        'reminder_days' => $mybb->get_input('reminder_days', MyBB::INPUT_INT),
    ], (int)$mybb->user['uid']);

    redirect("logbook.php?action=config&saved=1");

// =============================================================================
// Szene als abgeschlossen/aktiv umschalten
// =============================================================================
} elseif ($action === 'scene_change_finished') {
    verify_post_check($mybb->get_input('my_post_key'));

    $sceneId = $mybb->get_input('scene_id', MyBB::INPUT_INT);
    $userId  = (int)$mybb->user['uid'];

    if ($sceneId > 0 && logbook_db_is_participant($sceneId, $userId)) {
        logbook_change_scene_finished($sceneId, $userId);
    }

    redirect("logbook.php?action=usercp_view");

// =============================================================================
// Szene manuell hinzufügen (Formular)
// =============================================================================
} elseif ($action === 'scene_add') {
    $availableUsers = logbook_get_users_options();
    $userId         = (int)$mybb->user['uid'];
    $scene_type_sel = [0 => '', 1 => '', 2 => '', 3 => ''];

    eval("\$page = \"" . $templates->get("logbook_add_scene") . "\";");
    output_page($page);

// =============================================================================
// Szene speichern (neu)
// =============================================================================
} elseif ($action === 'scene_save') {
    verify_post_check($mybb->get_input('my_post_key'));

    $scene_type = $mybb->get_input('scene_type', MyBB::INPUT_INT);
    $scene_type = in_array($scene_type, [0, 1, 2, 3], true) ? $scene_type : 0;

    $scene_data = [
        'tid'        => ($scene_type >= 2) ? 0 : $mybb->get_input('threadId', MyBB::INPUT_INT),
        'ort'        => $mybb->get_input('ort'),
        'jahr'       => $mybb->get_input('jahr',       MyBB::INPUT_INT),
        'monat'      => $mybb->get_input('monat',      MyBB::INPUT_INT),
        'tag'        => $mybb->get_input('tag',        MyBB::INPUT_INT),
        'zeit'       => $mybb->get_input('zeit',       MyBB::INPUT_INT),
        'free_order' => $mybb->get_input('free_order', MyBB::INPUT_INT),
        'scene_type' => $scene_type,
        'visibility'  => ($scene_type >= 2) ? $mybb->get_input('visibility', MyBB::INPUT_INT) : 1,
        'title'      => ($scene_type >= 2) ? $mybb->get_input('scene_title') : '',
    ];
    $raw_users = $mybb->get_input('user', MyBB::INPUT_ARRAY);

    logbook_save_scene($scene_data, $raw_users);
    redirect("logbook.php");

// =============================================================================
// Szene bearbeiten (Formular)
// =============================================================================
} elseif ($action === 'scene_edit') {
    $id = $mybb->get_input('sceneId', MyBB::INPUT_INT);

    if ($id > 0 && logbook_db_is_participant($id, (int)$mybb->user['uid'])) {
        $data    = logbook_db_get_scene($id);
        $logbook = logbook_db_get_logbook((int)$mybb->user['uid']);
        $entry   = $logbook ? logbook_db_get_entry((int)$logbook['id'], $id) : null;

        if ($data) {
            $time     = [];
            $time[$data['time'] . '']     = ' selected';
            $entry_finished_val           = $entry ? (int)($entry['finished'] ?? 0) : 0;
            $finished                     = [];
            $finished[$entry_finished_val . ''] = ' selected';
            $free_order_checked    = (int)($data['free_order'] ?? 0) === 1 ? ' checked' : '';
            $scene_type_sel        = [0 => '', 1 => '', 2 => '', 3 => ''];
            $scene_type_sel[(int)($data['scene_type'] ?? 0)] = ' selected';
            $visibility_sel        = [0 => '', 1 => ''];
            $visibility_sel[(int)($data['visibility'] ?? 1)] = ' checked';
            $entry_reminder_days   = $entry ? (int)($entry['reminder_days'] ?? 0) : 0;

            // Teilnehmerverwaltung: Liste aller Teilnehmer inkl. Entfernen-Button
            // für eigene/verknüpfte Accounts (löst versehentlich erfasste Accounts).
            $current_uid      = (int)$mybb->user['uid'];
            $participants_raw = logbook_db_get_participants_raw($id);
            $participant_rows = '';
            foreach ($participants_raw as $p) {
                $p_uid   = (int)$p['user_id'];
                $p_name  = htmlspecialchars_uni((string)$p['username']);
                if ((int)$p['finished'] === 1) {
                    $p_name = '<span class="lb-participant-former">('
                            . $lang->logbook_participant_former . ' ' . $p_name . ')</span>';
                }

                $action_cell = '';
                if (logbook_user_can_manage_uid($current_uid, $p_uid)) {
                    $action_cell = '<form method="post" action="logbook.php" class="lb-inline-form"'
                                 . ' onsubmit="return confirm(\'' . addslashes($lang->logbook_confirm_remove_participant) . '\');">'
                                 . '<input type="hidden" name="action" value="scene_remove_participant" />'
                                 . '<input type="hidden" name="my_post_key" value="' . $post_key . '" />'
                                 . '<input type="hidden" name="sceneId" value="' . $id . '" />'
                                 . '<input type="hidden" name="targetUid" value="' . $p_uid . '" />'
                                 . '<button type="submit" class="button lb-action">'
                                 . $lang->logbook_action_remove_participant . '</button>'
                                 . '</form>';
                }

                $participant_rows .= '<tr class="trow1"><td>' . $p_name . '</td>'
                                   . '<td class="lb-align-right">' . $action_cell . '</td></tr>';
            }

            eval("\$page = \"" . $templates->get("logbook_scene_edit") . "\";");
            output_page($page);
        }
    }

// =============================================================================
// Szene speichern (Bearbeitung)
// =============================================================================
} elseif ($action === 'scene_edit_save') {
    verify_post_check($mybb->get_input('my_post_key'));

    $id = $mybb->get_input('sceneId', MyBB::INPUT_INT);

    if ($id > 0 && logbook_db_is_participant($id, (int)$mybb->user['uid'])) {
        $scene_type = $mybb->get_input('scene_type', MyBB::INPUT_INT);
        $scene_type = in_array($scene_type, [0, 1, 2, 3], true) ? $scene_type : 0;
        logbook_db_scene_update(
            $id,
            ($scene_type >= 2) ? 0 : $mybb->get_input('threadId', MyBB::INPUT_INT),
            $mybb->get_input('ort'),
            $mybb->get_input('jahr',       MyBB::INPUT_INT),
            $mybb->get_input('monat',      MyBB::INPUT_INT),
            $mybb->get_input('tag',        MyBB::INPUT_INT),
            $mybb->get_input('zeit',       MyBB::INPUT_INT),
            $mybb->get_input('free_order', MyBB::INPUT_INT),
            $scene_type,
            ($scene_type >= 2) ? $mybb->get_input('visibility', MyBB::INPUT_INT) : 1,
            ($scene_type >= 2) ? $mybb->get_input('scene_title') : ''
        );

        logbook_update_entry(
            (int)$mybb->user['uid'],
            $id,
            $mybb->get_input('description')
        );

        logbook_db_entry_update_reminder(
            (int)$mybb->user['uid'],
            $id,
            $mybb->get_input('entry_reminder_days', MyBB::INPUT_INT)
        );

        logbook_db_entry_update_finished(
            (int)$mybb->user['uid'],
            $id,
            $mybb->get_input('finished', MyBB::INPUT_INT)
        );
    }

    redirect("logbook.php?action=usercp_view");

// =============================================================================
// Teilnehmer aus einer Szene entfernen
// =============================================================================
} elseif ($action === 'scene_remove_participant') {
    verify_post_check($mybb->get_input('my_post_key'));

    $id         = $mybb->get_input('sceneId',   MyBB::INPUT_INT);
    $target_uid = $mybb->get_input('targetUid', MyBB::INPUT_INT);
    $current_uid = (int)$mybb->user['uid'];

    // Nur Szenen-Teilnehmer dürfen die Szene bearbeiten, und man darf nur
    // eigene bzw. verknüpfte Accounts (as_uid-Familie) entfernen.
    if ($id > 0
        && $target_uid > 0
        && logbook_db_is_participant($id, $current_uid)
        && logbook_user_can_manage_uid($current_uid, $target_uid)) {
        logbook_db_remove_participant($id, $target_uid);
    }

    redirect("logbook.php?action=scene_edit&sceneId=" . $id);

// =============================================================================
// Logbook Popup-Ansicht
// =============================================================================
} elseif ($action === 'popup_view') {
    $userId      = $mybb->get_input('userId', MyBB::INPUT_INT) ?: (int)$mybb->user['uid'];
    $scenes      = logbook_db_get_complete_logbook($userId);
    $username    = htmlspecialchars_uni((string)logbook_db_get_username($userId));
    $logbook_popup_header = $lang->sprintf($lang->logbook_popup_header_user, $username);
    $logbook     = logbook_db_get_logbook($userId);
    $description = $logbook ? htmlspecialchars_uni($logbook['description']) : '';
    $templateId  = $logbook ? (int)$logbook['template_id'] : 1;
    $viewbit     = '';

    $viewing_own_popup = $userId === (int)$mybb->user['uid'];

    foreach ($scenes as $scene) {
        // Note: the legacy `accepted` flag (invitation acceptance) is no longer
        // used – scenes are auto-accepted on creation. Filtering on it here hid
        // legacy entries with accepted = 0, even though the character still shows
        // up in the scene's participant list. Do not filter by `accepted`.

        // Private unplayed/planned scenes are only visible to the owner
        $scene_type_val = (int)($scene['scene_type'] ?? 0);
        if (!$viewing_own_popup && $scene_type_val >= 2 && (int)($scene['visibility'] ?? 1) === 0) {
            continue;
        }

        $v_tag         = (int)$scene['tag'];
        $v_monat       = logbook_get_month((int)$scene['monat']);
        $v_jahr        = (int)$scene['jahr'];
        $v_ort         = htmlspecialchars_uni($scene['ort']);
        $v_zeit        = logbook_get_time((int)$scene['time']);
        $v_teilnehmer  = logbook_get_participant_popup_string(
            (int)$scene['scene_id'],
            $templateId
        );
        $topic              = logbook_get_thread_link((int)$scene['tid'], (string)($scene['title'] ?? ''));
        $v_scene_type_badge = logbook_get_scene_type_badge($scene_type_val);
        $v_beschreibung     = htmlspecialchars_uni((string)$scene['text']);

        if ((int)$scene['finished'] === 0) {
            eval("\$v_aktuell = \"" . $templates->get("logbook_scene_unfinished_edit_guest") . "\";");
        } else {
            eval("\$v_aktuell = \"" . $templates->get("logbook_scene_finished_edit_guest") . "\";");
        }

        eval("\$viewbit .= \"" . $templates->get("logbook_popup_" . $templateId . "_viewbit") . "\";");
    }

    eval("\$page = \"" . $templates->get("logbook_popup_" . $templateId . "_view") . "\";");
    output_page($page);

// =============================================================================
// Anleitung
// =============================================================================
} elseif ($action === 'anleitung') {
    eval("\$page = \"" . $templates->get("logbook_anleitung") . "\";");
    output_page($page);

// =============================================================================
// Aktuelle Szenen aller verknüpften Charaktere
// =============================================================================
} elseif ($action === 'my_scenes') {
    $view_uid    = $mybb->get_input('userId', MyBB::INPUT_INT) ?: (int)$mybb->user['uid'];
    $viewing_own = $view_uid === (int)$mybb->user['uid'];
    $linked_uids = logbook_db_get_linked_user_ids($view_uid);

    $page_title = $viewing_own
        ? $lang->logbook_page_my_scenes_title
        : $lang->sprintf($lang->logbook_my_scenes_title_user, htmlspecialchars_uni((string)logbook_db_get_username($view_uid)));

    // --- Filter & sort params ---
    $filter_open     = $mybb->get_input('filter_open', MyBB::INPUT_INT) ? 1 : 0;
    $raw_category    = $mybb->get_input('filter_category');
    $filter_category = in_array($raw_category, ['', '0', '1'], true) ? $raw_category : '';
    $raw_days        = $mybb->get_input('filter_days', MyBB::INPUT_INT);
    $filter_days     = ($raw_days >= 0) ? $raw_days : 0;
    $raw_month       = $mybb->get_input('filter_month');
    $filter_month    = (preg_match('/^\d{4}-\d{1,2}$/', $raw_month)) ? $raw_month : '';
    $raw_sort        = $mybb->get_input('sort');
    $sort            = in_array($raw_sort, ['', 'lastpost_asc', 'lastpost_desc', 'date_asc', 'date_desc'], true)
                       ? $raw_sort : '';
    $filter_no_grouping = $mybb->get_input('filter_no_grouping', MyBB::INPUT_INT) ? 1 : 0;

    // Collect logbook IDs upfront (needed for month query and main loop)
    $all_logbook_ids = [];
    foreach ($linked_uids as $char_uid) {
        $lid = logbook_db_get_logbook_id($char_uid);
        if ($lid) {
            $all_logbook_ids[$char_uid] = $lid;
        }
    }

    // Available months for filter dropdown
    $available_months          = logbook_db_get_active_months_for_logbooks(array_values($all_logbook_ids));
    $filter_month_options_html = '<option value=""' . ($filter_month === '' ? ' selected' : '') . '>'
                                 . $lang->logbook_filter_month_all . '</option>';
    foreach ($available_months as $am) {
        $am_val    = $am['jahr'] . '-' . $am['monat'];
        $am_label  = logbook_get_month($am['monat']) . ' ' . $am['jahr'];
        $am_sel    = ($filter_month === $am_val) ? ' selected' : '';
        $filter_month_options_html .= '<option value="' . htmlspecialchars_uni($am_val) . '"' . $am_sel . '>'
                                      . htmlspecialchars_uni($am_label) . '</option>';
    }

    // Category options
    $filter_cat_options = '<option value=""'  . ($filter_category === ''  ? ' selected' : '') . '>' . $lang->logbook_filter_cat_all    . '</option>'
                        . '<option value="0"' . ($filter_category === '0' ? ' selected' : '') . '>' . $lang->logbook_scene_type_normal . '</option>'
                        . '<option value="1"' . ($filter_category === '1' ? ' selected' : '') . '>' . $lang->logbook_scene_type_au     . '</option>';

    $filter_days_value = $filter_days > 0 ? $filter_days : '';

    // Sort options
    $sort_map = [
        ''              => 'logbook_sort_default',
        'lastpost_asc'  => 'logbook_sort_lastpost_asc',
        'lastpost_desc' => 'logbook_sort_lastpost_desc',
        'date_asc'      => 'logbook_sort_date_asc',
        'date_desc'     => 'logbook_sort_date_desc',
    ];
    $sort_options = '';
    foreach ($sort_map as $sv => $lk) {
        $sv_sel        = ($sort === $sv) ? ' selected' : '';
        $sort_options .= '<option value="' . htmlspecialchars_uni($sv) . '"' . $sv_sel . '>'
                         . htmlspecialchars_uni($lang->$lk) . '</option>';
    }

    $filter_open_checked        = $filter_open ? ' checked' : '';
    $filter_no_grouping_checked = $filter_no_grouping ? ' checked' : '';
    $filter_userid_input  = !$viewing_own ? '<input type="hidden" name="userId" value="' . $view_uid . '" />' : '';
    $filter_reset_suffix  = !$viewing_own ? '&amp;userId=' . $view_uid : '';

    eval("\$filter_section = \"" . $templates->get('logbook_my_scenes_filter') . "\";");

    $result = logbook_render_my_scenes_characters(
        $linked_uids,
        $all_logbook_ids,
        $view_uid,
        $viewing_own,
        $filter_category,
        $filter_days,
        $filter_month,
        $filter_open,
        $sort,
        $filter_no_grouping
    );

    $characters        = $result['characters'];
    $total_open_scenes = $result['total_open_scenes'];
    $total_min_open    = $result['total_min_open'];
    $total_max_open    = $result['total_max_open'];

    if ($total_min_open === $total_max_open) {
        $total_open_label = $lang->sprintf($lang->logbook_my_scenes_open_of_total, $total_min_open, $total_open_scenes);
    } else {
        $total_open_label = $lang->sprintf($lang->logbook_my_scenes_open_range_of_total, $total_min_open, $total_max_open, $total_open_scenes);
    }

    eval("\$page = \"" . $templates->get('logbook_my_scenes') . "\";");
    output_page($page);

// =============================================================================
// Logbook-Eintrag löschen
// =============================================================================
} elseif ($action === 'delete_entry') {
    verify_post_check($mybb->get_input('my_post_key'));

    $entryId = $mybb->get_input('entryId', MyBB::INPUT_INT);
    if ($entryId > 0) {
        logbook_db_delete_entry((int)$mybb->user['uid'], $entryId);
    }

    redirect("logbook.php?action=usercp_view");

// =============================================================================
// Sich selbst zu einer Szene hinzufügen (Formular)
// =============================================================================
} elseif ($action === 'add_participant') {
    $error = '';
    eval("\$page = \"" . $templates->get("logbook_add_participant") . "\";");
    output_page($page);

// =============================================================================
// Sich selbst zu einer Szene hinzufügen (Speichern)
// =============================================================================
} elseif ($action === 'do_add_participant') {
    verify_post_check($mybb->get_input('my_post_key'));

    $sceneId = $mybb->get_input('sceneId', MyBB::INPUT_INT);
    $text    = $mybb->get_input('text');

    if ($sceneId > 0) {
        logbook_add_user_to_scene($sceneId, $text);
        redirect("logbook.php");
        exit();
    } else {
        $error = '<div class="error">' . $lang->logbook_error_scene_id . '</div>';
        eval("\$page = \"" . $templates->get("logbook_add_participant") . "\";");
        output_page($page);
    }
}
