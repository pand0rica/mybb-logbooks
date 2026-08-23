<?php
/**
 * Logbook Plugin – Hauptdatei
 *
 * Verwaltet Inplay-Szenen auf RPG-Foren. Jeder Benutzer hat ein Logbook,
 * in das er an Szenen teilnehmen, diese akzeptieren und bearbeiten kann.
 *
 * @package   Logbook
 * @author    pand0rica
 * @version   2.0.0
 * @license   GPL v3
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

// --- Hook-Registrierungen ---
$plugins->add_hook("newthread_start",            "logbook_handle_new_reply");
$plugins->add_hook("newthread_do_newthread_end", "logbook_handle_do_new_thread");
$plugins->add_hook("index_start",                "logbook_handle_index");
$plugins->add_hook("forumdisplay_thread_end",    "logbook_handle_thread_display");
$plugins->add_hook("showthread_start",           "logbook_handle_showthread");
$plugins->add_hook("global_start",                "logbook_register_myalerts_formatter");
$plugins->add_hook("xmlhttp",                     "logbook_register_myalerts_formatter");
$plugins->add_hook("usercp_menu",                 "logbook_usercp_nav");
$plugins->add_hook("global_intermediate",         "logbook_handle_header_link");

// =============================================================================
// Plugin-Lifecycle
// =============================================================================

/**
 * Gibt die Plugin-Metadaten zurück.
 *
 * @return array Plugin-Informationen für das ACP
 */
function logbook_info(): array
{
    return [
        "name"          => "Logbook",
        "description"   => "Verwaltet Inplay-Szenen im Logbook der Benutzer.",
        "website"       => "https://github.com/pand0rica/logbooks",
        "author"        => "pand0rica",
        "authorsite"    => "https://github.com/pand0rica",
        "version"       => "2.0.0",
        "guid"          => "",
        "codename"      => "logbook",
        "compatibility" => "18*",
    ];
}

/**
 * Erstellt die benötigten Datenbanktabellen bei der Installation.
 *
 * @return void
 */
function logbook_install(): void
{
    global $db;

    $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "logbook` (
        `id`          INT(11) NOT NULL AUTO_INCREMENT,
        `description` TEXT COLLATE utf8_unicode_ci,
        `user_id`     INT(11) NOT NULL,
        `template_id` INT(11) DEFAULT 1,
        `reminder_days` INT(4) NOT NULL DEFAULT 0,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "logbook_entry` (
        `id`         INT(11) NOT NULL AUTO_INCREMENT,
        `scene_id`   INT(11) NOT NULL,
        `accepted`   TINYINT(1) DEFAULT 0,
        `finished`   TINYINT(1) NOT NULL DEFAULT 0,
        `logbook_id` INT(11) NOT NULL,
        `text`       TEXT COLLATE utf8_unicode_ci,
        `reminder_days` INT(4) NOT NULL DEFAULT 0,
        `name`       TEXT COLLATE utf8_unicode_ci,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

    $db->query("CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX . "scene` (
        `id`         INT(11) NOT NULL AUTO_INCREMENT,
        `tid`        INT(11) NOT NULL DEFAULT 0,
        `free_order` TINYINT(1) NOT NULL DEFAULT 0,
        `ort`        TEXT COLLATE utf8_unicode_ci,
        `jahr`       INT(11) DEFAULT NULL,
        `monat`      INT(11) DEFAULT NULL,
        `tag`        INT(11) DEFAULT NULL,
        `time`       INT(11) DEFAULT NULL,
        `scene_type` TINYINT(1) NOT NULL DEFAULT 0,
        `visibility`  TINYINT(1) NOT NULL DEFAULT 1,
        `title`      VARCHAR(255) COLLATE utf8_unicode_ci DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");
}

/**
 * Prüft, ob das Plugin bereits installiert ist.
 *
 * @return bool true wenn installiert
 */
function logbook_is_installed(): bool
{
    global $db;
    return $db->table_exists("scene");
}

/**
 * Entfernt alle Plugin-Tabellen bei der Deinstallation.
 *
 * @return void
 */
function logbook_uninstall(): void
{
    global $db;

    // Settings persist across deactivation and are only removed on a full
    // uninstall (mirrors logbook_activate()).
    $db->delete_query('settings', "name IN ('logbook_inplay_forums','logbook_allowed_groups','logbook_reminder_days','logbook_archive_forum')");
    $db->delete_query('settinggroups', "name = 'logbook'");
    rebuild_settings();

    $db->drop_table("logbook_entry");
    $db->drop_table("logbook");
    $db->drop_table("scene");
}

/**
 * Aktiviert das Plugin: Templates laden, Frontend-Datei kopieren,
 * Plugin-Settings anlegen, Core-Templates modifizieren.
 *
 * @return void
 */
function logbook_activate(): void
{
    global $db;

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    // Register stylesheet in MyBB theme system for all themes
    $css_path = MYBB_ROOT . "inc/plugins/logbooks/assets/logbook.css";
    if (file_exists($css_path)) {
        $css = $db->escape_string((string)file_get_contents($css_path));
        $themes = $db->simple_select("themes", "tid");
        while ($theme = $db->fetch_array($themes)) {
            $tid = (int)$theme["tid"];
            $existing = $db->fetch_array($db->simple_select(
                "themestylesheets", "sid",
                "name = 'logbook.css' AND tid = '" . $tid . "'"
            ));
            if ($existing) {
                $db->update_query("themestylesheets", [
                    "stylesheet"   => $css,
                    "lastmodified" => TIME_NOW,
                ], "sid='" . (int)$existing["sid"] . "'", 1);
            } else {
                $sid = $db->insert_query("themestylesheets", [
                    "name"         => "logbook.css",
                    "tid"          => $tid,
                    "attachedto"   => "",
                    "stylesheet"   => $css,
                    "lastmodified" => TIME_NOW,
                    "cachefile"    => "logbook.css",
                ]);
                $db->update_query("themestylesheets",
                    ["cachefile" => "css.php?stylesheet={$sid}"],
                    "sid='{$sid}'", 1
                );
            }
            update_theme_stylesheet_list($tid);
        }
    }

    // Plugin-Templates aus .tpl-Dateien in die DB laden
    $template_dir = MYBB_ROOT . "inc/plugins/logbooks/templates/";
    if (is_dir($template_dir)) {
        $handle = opendir($template_dir);
        if ($handle) {
            while (($file = readdir($handle)) !== false) {
                if (substr($file, -4) !== '.tpl') {
                    continue;
                }
                $title    = substr($file, 0, -4);
                $content  = (string)file_get_contents($template_dir . $file);
                $db->insert_query('templates', [
                    'title'    => $db->escape_string($title),
                    'template' => $db->escape_string($content),
                    'sid'      => -1,
                    'version'  => '',
                    'dateline' => time(),
                ]);
            }
            closedir($handle);
        }
    }

    // Plugin-Settings anlegen bzw. bei Upgrade fehlende Einzel-Settings ergänzen.
    // Die settinggroup-Existenz allein darf nicht den ganzen Block überspringen,
    // sonst fehlen bei Upgrades neu hinzugekommene Settings (z.B. logbook_archive_forum).
    $setting_defs = [
        [
            'name'        => 'logbook_inplay_forums',
            'title'       => 'In-Play Forums',
            'description' => 'Select all forums that are treated as in-play forums.',
            'optionscode' => 'forumselect',
            'value'       => '',
            'disporder'   => 1,
        ],
        [
            'name'        => 'logbook_allowed_groups',
            'title'       => 'Allowed User Groups',
            'description' => 'Select all user groups that may use the logbook. Select none to allow all groups.',
            'optionscode' => 'groupselect',
            'value'       => '',
            'disporder'   => 2,
        ],
        [
            'name'        => 'logbook_reminder_days',
            'title'       => 'Posting Reminder (Days)',
            'description' => 'Global default: show a reminder on the index if the user has not posted in a scene for this many days. 0 = disabled.',
            'optionscode' => 'text',
            'value'       => '0',
            'disporder'   => 3,
        ],
        [
            'name'        => 'logbook_archive_forum',
            'title'       => 'Archive Forum',
            'description' => 'Threads whose scene has been finished by all participants are moved to this forum. Select none to disable moving.',
            'optionscode' => 'forumselect',
            'value'       => '',
            'disporder'   => 4,
        ],
    ];

    $group = $db->fetch_array($db->simple_select('settinggroups', 'gid', "name = 'logbook'"));
    if ($group) {
        $gid = (int)$group['gid'];
    } else {
        $gid = (int)$db->insert_query('settinggroups', [
            'name'        => 'logbook',
            'title'       => 'Logbook',
            'description' => 'Settings for the Logbook plugin.',
            'disporder'   => 1,
            'isdefault'   => 0,
        ]);
    }

    foreach ($setting_defs as $def) {
        $exists = $db->fetch_array($db->simple_select(
            'settings', 'sid', "name = '" . $db->escape_string($def['name']) . "'"
        ));
        if ($exists) {
            continue; // keep existing value on upgrade/reactivation
        }
        $def['gid'] = $gid;
        $db->insert_query('settings', $def);
    }

    rebuild_settings();

    logbook_install_alert_type();

    logbook_modify_templates();
}

/**
 * Deaktiviert das Plugin: eigene Templates entfernen,
 * Plugin-Settings entfernen, Core-Template-Modifikationen rückgängig machen.
 *
 * @return void
 */
function logbook_deactivate(): void
{
    global $db;

    require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

    logbook_remove_templates();

    logbook_uninstall_alert_type();

    // Remove stylesheet from all themes
    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'logbook.css'");
    $themes = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($themes)) {
        update_theme_stylesheet_list((int)$theme["tid"]);
    }

    // Alle Plugin-eigenen Templates aus der DB entfernen
    $db->delete_query('templates', "title LIKE 'logbook%'");

    // Note: plugin settings and the settinggroup are intentionally NOT removed
    // here – they persist across deactivation/reactivation so the admin's
    // configuration is kept. They are only removed in logbook_uninstall().
}

/**
 * Injiziert eine Variable per str_replace in alle Versionen eines Templates
 * (Master sid=-2 sowie alle theme-spezifischen Kopien sid>0 / sid=-1).
 * Idempotent: bereits enthaltene Variablen werden nicht doppelt eingefügt.
 *
 * @param string $title   Template-Name (z.B. "newthread")
 * @param string $needle  Zu suchende Variable im Template, z.B. '{$postoptions}'
 * @param string $replace Ersetzung, z.B. '{$logbook_entry_add}'."\n".'{$postoptions}'
 * @param string $guard   String dessen Vorhandensein eine erneute Injection verhindert
 * @return void
 */
function logbook_inject_template(string $title, string $needle, string $replace, string $guard): void
{
    global $db;

    $query = $db->simple_select(
        'templates',
        'tid, template',
        "title = '" . $db->escape_string($title) . "' AND (sid = -2 OR sid = -1 OR sid > 0)"
    );

    while ($tpl = $db->fetch_array($query)) {
        if (strpos($tpl['template'], $guard) !== false) {
            continue; // already injected
        }
        $new = str_replace($needle, $replace, $tpl['template']);
        if ($new !== $tpl['template']) {
            $db->update_query('templates', ['template' => $db->escape_string($new)], "tid = '" . (int)$tpl['tid'] . "'");
        }
    }
}

/**
 * Entfernt eine Variable per str_replace aus allen Versionen eines Templates.
 *
 * @param string $title  Template-Name
 * @param string $remove Zu entfernender String (inkl. ggf. Zeilenumbruch)
 * @return void
 */
function logbook_remove_from_template(string $title, string $remove): void
{
    global $db;

    $query = $db->simple_select(
        'templates',
        'tid, template',
        "title = '" . $db->escape_string($title) . "' AND (sid = -2 OR sid = -1 OR sid > 0)"
    );

    while ($tpl = $db->fetch_array($query)) {
        $new = str_replace($remove, '', $tpl['template']);
        if ($new !== $tpl['template']) {
            $db->update_query('templates', ['template' => $db->escape_string($new)], "tid = '" . (int)$tpl['tid'] . "'");
        }
    }
}

/**
 * Gibt die UserCP-Navigationszeile (Logbook-Link) als HTML zurück.
 *
 * @return string HTML-Zeile für die UserCP-Navigation
 */
function logbook_usercp_nav_template(): string
{
    return '<tr><td class="trow1 smalltext"><a href="logbook.php" class="usercp_nav_item usercp_nav_logbook">{$lang->logbook_usercp_nav_link}</a></td></tr>';
}

function logbook_add_usercp_nav_link(): void
{
    $drafts_link = '<tr><td class="trow1 smalltext"><a href="usercp.php?action=drafts" class="usercp_nav_item usercp_nav_drafts">{$draftcount}</a></td></tr>';
    find_replace_templatesets(
        'usercp_nav_misc',
        '#' . preg_quote($drafts_link) . '#i',
        $drafts_link . "\n" . logbook_usercp_nav_template()
    );
}

function logbook_remove_usercp_nav_link(): void
{
    find_replace_templatesets(
        'usercp_nav_misc',
        '#' . preg_quote("\n" . logbook_usercp_nav_template()) . '#i',
        ''
    );
}

/**
 * Fügt die Plugin-Variablen in bestehende MyBB-Core-Templates ein
 * (newthread, index, showthread, header, usercp_nav_misc).
 *
 * @return void
 */
function logbook_modify_templates(): void
{
    logbook_inject_template(
        'newthread',
        '{$posticons}',
        '{$logbook_entry_add}' . "\n" . '{$posticons}',
        '{$logbook_entry_add}'
    );

    logbook_inject_template(
        'index',
        '{$forums}',
        '{$logbookAccept}' . '{$forums}',
        '{$logbookAccept}'
    );

    logbook_inject_template(
        'showthread',
        '{$posts}',
        '{$logbook_scene_info}' . "\n" . '{$posts}',
        '{$logbook_scene_info}'
    );

    logbook_inject_template(
        'header',
        '{$menu_memberlist}',
        '{$menu_memberlist}' . "\n\t\t\t\t\t\t{" . '$logbook_header_link}',
        '{$logbook_header_link}'
    );

    logbook_add_usercp_nav_link();
}

/**
 * Entfernt die Plugin-Variablen aus den MyBB-Core-Templates.
 *
 * @return void
 */
function logbook_remove_templates(): void
{
    logbook_remove_from_template('newthread',  '{$logbook_entry_add}' . "\n");
    logbook_remove_from_template('index',      '{$logbookAccept}');
    logbook_remove_from_template('showthread', '{$logbook_scene_info}' . "\n");
    logbook_remove_from_template('header',     "\n\t\t\t\t\t\t{" . '$logbook_header_link}');
    logbook_remove_usercp_nav_link();
}

// =============================================================================
// DB-Funktionen – CREATE
// =============================================================================

/**
 * Erstellt eine neue Szene in der Datenbank.
 *
 * @param int      $tid      Thread-ID
 * @param string   $ort      Ort der Szene
 * @param int      $jahr     Fiktives Jahr
 * @param int      $monat    Monat (1–12)
 * @param int      $tag      Tag (1–31)
 * @param int      $time     Tageszeit (1–5)
 * @return array|false Erzeugte Szene mit ID oder false bei Fehler
 */
function logbook_db_scene_create(
    int $tid,
    string $ort,
    int $jahr,
    int $monat,
    int $tag,
    int $time,
    int $free_order = 0,
    int $scene_type = 0,
    int $visibility = 1,
    string $title = ''
) {
    global $db;

    $data = [
        'tid'        => $tid,
        'free_order' => $free_order,
        'ort'        => $db->escape_string($ort),
        'jahr'       => $jahr,
        'monat'      => $monat,
        'tag'        => $tag,
        'time'       => $time,
        'scene_type' => $scene_type,
        'visibility'  => $visibility,
        'title'      => $db->escape_string($title),
    ];

    $id = $db->insert_query('scene', $data);
    if ($id) {
        $data['id'] = $id;
        return $data;
    }

    return false;
}

/**
 * Erstellt ein neues Logbook für einen Benutzer.

 *
 * @param string $description Beschreibung
 * @param int    $user_id     Benutzer-ID
 * @param int    $template_id Template-ID
 * @return int|false Neue Logbook-ID oder false bei Fehler
 */
function logbook_db_logbook_create(
    string $description,
    int $user_id,
    int $template_id
) {
    global $db;

    return $db->insert_query('logbook', [
        'description' => $db->escape_string($description),
        'user_id'     => $user_id,
        'template_id' => $template_id,
    ]);
}

/**
 * Erstellt einen neuen Logbook-Eintrag für eine Szene.
 *
 * @param int    $scene_id    Szene-ID
 * @param int    $logbook_id  Logbook-ID
 * @param string $name        Name (optional)
 * @param int    $accepted    Akzeptiert (0/1)
 * @param string $text        Beschreibung / Zusammenfassung
 * @return int|false Neue Eintrag-ID oder false bei Fehler
 */
function logbook_db_entry_create(
    int $scene_id,
    int $logbook_id,
    string $name,
    int $accepted,
    string $text
) {
    global $db;

    return $db->insert_query('logbook_entry', [
        'scene_id'   => $scene_id,
        'logbook_id' => $logbook_id,
        'name'       => $db->escape_string($name),
        'accepted'   => $accepted,
        'text'       => $db->escape_string($text),
    ]);
}

// =============================================================================
// DB-Funktionen – READ
// =============================================================================

/**
 * Gibt alle Logbook-Einträge mit zugehöriger Szene für einen Benutzer zurück,
 * sortiert nach Datum absteigend.
 *
 * @param int $user_id Benutzer-ID
 * @return array Liste der Einträge
 */
function logbook_db_get_complete_logbook(int $user_id): array
{
    global $db;

    $result = $db->query("
        SELECT *,
               " . TABLE_PREFIX . "scene.id         AS scene_id,
               " . TABLE_PREFIX . "logbook_entry.id AS eid
        FROM " . TABLE_PREFIX . "logbook_entry
        JOIN " . TABLE_PREFIX . "scene ON scene_id = " . TABLE_PREFIX . "scene.id
        WHERE logbook_id = (
            SELECT id FROM " . TABLE_PREFIX . "logbook WHERE user_id = " . $user_id . "
        )
        ORDER BY jahr DESC, monat DESC, tag DESC
    ");

    $data = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $data[] = $row;
        }
    }

    return $data;
}

/**
 * Ermittelt den nächsten Poster einer Szene.
 *
 * Zwei Fälle:
 * - Wenn ein Teilnehmer noch gar nicht im Thread gepostet hat: erster in DB-Eintragereihenfolge,
 *   der noch keinen Post hat (d.h. initiale Runde ist noch nicht abgeschlossen).
 * - Wenn alle mindestens einmal gepostet haben: derjenige mit dem ältesten letzten Post
 *   (= wartet am längsten).
 *
 * @param int $scene_id Szene-ID
 * @param int $tid      Thread-ID
 * @return array|null ['user_id' => int, 'username' => string] oder null
 */
function logbook_db_get_scene_next_poster(int $scene_id, int $tid): ?array
{
    global $db;

    $result = $db->query("
        SELECT l.user_id, u.username, e.id AS entry_order,
               MAX(p.dateline) AS last_post_time,
               COUNT(p.pid)    AS post_count
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        JOIN " . TABLE_PREFIX . "users u   ON l.user_id = u.uid
        LEFT JOIN " . TABLE_PREFIX . "posts p
               ON p.tid = " . $tid . " AND p.uid = l.user_id AND p.visible = 1
        WHERE e.scene_id = " . $scene_id . "
          AND e.finished = 0
        GROUP BY l.user_id, u.username, e.id
        ORDER BY e.id ASC
    ");

    if (!$result) {
        return null;
    }

    $participants = [];
    while ($row = $db->fetch_array($result)) {
        $participants[] = $row;
    }

    if (empty($participants)) {
        return null;
    }

    // First round not yet complete: return the first participant (by DB order) who hasn't posted
    foreach ($participants as $p) {
        if ((int)$p['post_count'] === 0) {
            return ['user_id' => (int)$p['user_id'], 'username' => $p['username']];
        }
    }

    // Everyone posted at least once: the one with the oldest last post is next
    usort($participants, static function (array $a, array $b): int {
        return (int)$a['last_post_time'] - (int)$b['last_post_time'];
    });

    return ['user_id' => (int)$participants[0]['user_id'], 'username' => $participants[0]['username']];
}

/**
 * Zählt aktive (unerledigte) Teilnehmer einer Szene.
 *
 * @param int $scene_id Szene-ID
 * @return int Anzahl aktiver Teilnehmer
 */
function logbook_scene_count_active_participants(int $scene_id): int
{
    global $db;

    $query = $db->query("
        SELECT COUNT(*) AS cnt
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        JOIN " . TABLE_PREFIX . "users u   ON l.user_id = u.uid
        WHERE e.scene_id = " . $scene_id . "
          AND e.finished = 0
    ");

    $row = $db->fetch_array($query);
    return (int)($row['cnt'] ?? 0);
}

/**
 * Gibt alle Teilnehmer einer Szene zurück.
 *
 * @param int $scene_id Szene-ID
 * @return array Teilnehmer-Liste
 */
function logbook_db_get_participants(int $scene_id): array
{
    global $db;

    // Show every recorded participant, exactly like the edit view
    // (logbook_db_get_participants_raw). Earlier versions filtered
    // "AND u.as_uid > 0", which silently hid any participant that is a main
    // account (as_uid = 0) – on forums where players post on their main
    // account this hid ALL participants everywhere except the edit view.
    $result = $db->query("
        SELECT l.user_id, u.username, e.finished
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        JOIN " . TABLE_PREFIX . "users u   ON l.user_id = u.uid
        WHERE e.scene_id = " . $scene_id . "
        ORDER BY e.id ASC
    ");

    $data = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $data[] = $row;
        }
    }

    return $data;
}

/**
 * Gibt ALLE Teilnehmer einer Szene zurück – ohne den as_uid-Filter.
 * Wird für die Teilnehmerverwaltung (Entfernen) benötigt, damit auch
 * versehentlich erfasste Hauptaccounts (as_uid = 0) sichtbar und entfernbar sind.
 *
 * @param int $scene_id Szene-ID
 * @return array Teilnehmer-Liste (user_id, username, finished, entry_id)
 */
function logbook_db_get_participants_raw(int $scene_id): array
{
    global $db;

    $result = $db->query("
        SELECT l.user_id, u.username, e.finished, e.id AS entry_id
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        JOIN " . TABLE_PREFIX . "users u   ON l.user_id = u.uid
        WHERE e.scene_id = " . $scene_id . "
        ORDER BY e.id ASC
    ");

    $data = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $data[] = $row;
        }
    }

    return $data;
}

/**
 * Entfernt einen Teilnehmer (dessen Logbook-Eintrag) aus einer Szene.
 * Anders als logbook_db_delete_entry() erlaubt dies das Entfernen eines
 * beliebigen Ziel-Accounts – die Berechtigungsprüfung erfolgt im Aufrufer.
 *
 * @param int $scene_id   Szene-ID
 * @param int $target_uid Zu entfernender Account
 * @return void
 */
function logbook_db_remove_participant(int $scene_id, int $target_uid): void
{
    global $db;

    $db->query("
        DELETE e
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        WHERE e.scene_id = " . $scene_id . "
          AND l.user_id = " . $target_uid . "
    ");
}

/**
 * Gibt die Logbook-ID eines Benutzers zurück.
 *
 * @param int $user_id Benutzer-ID
 * @return int|false Logbook-ID oder false wenn nicht gefunden
 */
function logbook_db_get_logbook_id(int $user_id)
{
    global $db;

    $result = $db->simple_select('logbook', 'id', 'user_id = ' . $user_id);
    if ($result) {
        $row = $db->fetch_array($result);
        return $row ? (int)$row['id'] : false;
    }

    return false;
}

/**
 * Gibt das Logbook eines Benutzers zurück.
 *
 * @param int $user_id Benutzer-ID
 * @return array|false Logbook-Array oder false wenn nicht gefunden
 */
function logbook_db_get_logbook(int $user_id)
{
    global $db;

    $result = $db->simple_select('logbook', '*', 'user_id = ' . $user_id);
    if ($result) {
        $row = $db->fetch_array($result);
        return $row ?: false;
    }

    return false;
}

/**
 * Gibt die Gesamtanzahl der Szenen eines Benutzers zurück.
 *
 * @param int $user_id Benutzer-ID
 * @return int Anzahl
 */
function logbook_db_get_scene_count(int $user_id): int
{
    global $db;

    $result = $db->query("
        SELECT COUNT(*) AS anz
        FROM " . TABLE_PREFIX . "logbook_entry
        JOIN " . TABLE_PREFIX . "scene ON scene_id = " . TABLE_PREFIX . "scene.id
        WHERE logbook_id = (
            SELECT id FROM " . TABLE_PREFIX . "logbook WHERE user_id = " . $user_id . "
        )
    ");

    if ($result) {
        $row = $db->fetch_array($result);
        return (int)($row['anz'] ?? 0);
    }

    return 0;
}

/**
 * Gibt die Anzahl der abgeschlossenen Szenen eines Benutzers zurück.
 *
 * @param int $user_id Benutzer-ID
 * @return int Anzahl
 */
function logbook_db_get_scene_finished_count(int $user_id): int
{
    global $db;

    $result = $db->query("
        SELECT COUNT(*) AS anz
        FROM " . TABLE_PREFIX . "logbook_entry
        JOIN " . TABLE_PREFIX . "scene ON scene_id = " . TABLE_PREFIX . "scene.id
        WHERE " . TABLE_PREFIX . "logbook_entry.finished = 1
          AND logbook_id = (
            SELECT id FROM " . TABLE_PREFIX . "logbook WHERE user_id = " . $user_id . "
          )
    ");

    if ($result) {
        $row = $db->fetch_array($result);
        return (int)($row['anz'] ?? 0);
    }

    return 0;
}

/**
 * Gibt die Anzahl der aktiven (nicht abgeschlossenen) Szenen zurück.
 *
 * @param int $user_id Benutzer-ID
 * @return int Anzahl
 */
function logbook_db_get_scene_unfinished_count(int $user_id): int
{
    global $db;

    $result = $db->query("
        SELECT COUNT(*) AS anz
        FROM " . TABLE_PREFIX . "logbook_entry
        JOIN " . TABLE_PREFIX . "scene ON scene_id = " . TABLE_PREFIX . "scene.id
        WHERE " . TABLE_PREFIX . "logbook_entry.finished = 0
          AND logbook_id = (
            SELECT id FROM " . TABLE_PREFIX . "logbook WHERE user_id = " . $user_id . "
          )
    ");

    if ($result) {
        $row = $db->fetch_array($result);
        return (int)($row['anz'] ?? 0);
    }

    return 0;
}

/**
 * Gibt eine Szene anhand ihrer ID zurück.
 *
 * @param int $scene_id Szene-ID
 * @return array|null Szene-Array oder null wenn nicht gefunden
 */
function logbook_db_get_scene(int $scene_id): ?array
{
    global $db;

    $result = $db->query("SELECT * FROM " . TABLE_PREFIX . "scene WHERE id = " . $scene_id);
    if ($result) {
        $row = $db->fetch_array($result);
        return $row ?: null;
    }

    return null;
}

/**
 * Gibt eine Szene anhand der zugehörigen Thread-ID zurück.
 *
 * @param int $tid Thread-ID
 * @return array|null Szene-Array oder null wenn nicht gefunden
 */
function logbook_db_get_scene_by_tid(int $tid): ?array
{
    global $db;

    $result = $db->query("SELECT * FROM " . TABLE_PREFIX . "scene WHERE tid = " . $tid);
    if ($result) {
        $row = $db->fetch_array($result);
        return $row ?: null;
    }

    return null;
}

/**
 * Gibt alle aktiven Szenen eines Logbooks zurück (für Index-Seite).
 *
 * @param int $logbook_id Logbook-ID
 * @return array Liste der Szenen
 */
function logbook_db_get_scenes(int $logbook_id): array
{
    global $db;

    $result = $db->query("
        SELECT t.tid, t.lastposter, t.lastposteruid, t.lastpost, t.dateline, t.subject,
               s.jahr, s.monat, s.tag, s.ort, s.time, s.id, s.free_order,
               s.scene_type, s.title,
               e.id AS entryId, e.accepted
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "scene   s ON e.scene_id = s.id
        LEFT JOIN " . TABLE_PREFIX . "threads t ON t.tid = s.tid
        WHERE e.finished = 0
          AND s.scene_type IN (0, 1)
          AND logbook_id = " . $logbook_id . "
    ");

    $data = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $data[] = $row;
        }
    }

    return $data;
}

/**
 * Returns distinct (monat, jahr) combinations from all active scenes
 * across the given logbook IDs, ordered newest first.
 *
 * @param int[] $logbook_ids
 * @return array[] Each entry: ['monat' => int, 'jahr' => int]
 */
function logbook_db_get_active_months_for_logbooks(array $logbook_ids): array
{
    global $db;

    if (empty($logbook_ids)) {
        return [];
    }

    $ids_str = implode(',', array_map('intval', $logbook_ids));
    $result  = $db->query("
        SELECT DISTINCT s.monat, s.jahr
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "scene s ON e.scene_id = s.id
        WHERE e.logbook_id IN (" . $ids_str . ")
          AND e.finished = 0
          AND s.scene_type IN (0, 1)
          AND s.monat IS NOT NULL AND s.monat > 0
          AND s.jahr IS NOT NULL AND s.jahr > 0
        ORDER BY s.jahr DESC, s.monat DESC
    ");

    $data = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $data[] = ['monat' => (int)$row['monat'], 'jahr' => (int)$row['jahr']];
        }
    }

    return $data;
}

function logbook_db_get_total_scene_count(int $logbook_id): int
{
    global $db;

    $result = $db->query("
        SELECT COUNT(*) AS cnt
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "scene s ON e.scene_id = s.id
        WHERE e.logbook_id = " . $logbook_id . "
    ");

    if ($result && ($row = $db->fetch_array($result))) {
        return (int)$row['cnt'];
    }
    return 0;
}

/**
 * Gibt alle UIDs zurück, die über EnhancedAccountSwitcher mit dem Benutzer verknüpft sind.
 * Ermittelt den Root-Account via as_uid und sammelt alle Charaktere darunter.
 * Ohne AccountSwitcher oder wenn keine Verknüpfung existiert, wird nur die eigene UID zurückgegeben.
 *
 * @param int $uid Benutzer-ID
 * @return int[]
 */
function logbook_db_get_linked_user_ids(int $uid): array
{
    global $db;

    // as_uid column is added by EnhancedAccountSwitcher; gracefully fall back if absent
    if (!$db->field_exists('as_uid', 'users')) {
        return [$uid];
    }

    $row    = $db->fetch_array($db->simple_select('users', 'as_uid', 'uid = ' . $uid));
    $as_uid = $row ? (int)$row['as_uid'] : 0;
    $root   = ($as_uid > 0) ? $as_uid : $uid;

    $result = $db->query("
        SELECT uid FROM " . TABLE_PREFIX . "users
        WHERE as_uid = " . $root . "
        ORDER BY username ASC
    ");

    $uids = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $uids[] = (int)$row['uid'];
        }
    }

    return $uids ?: [$uid];
}

/**
 * Ermittelt den Root-Account (Hauptaccount) eines Benutzers via as_uid.
 * Ein Charakter (as_uid > 0) liefert seinen Hauptaccount, ein Hauptaccount
 * (as_uid = 0) liefert sich selbst. Ohne AccountSwitcher immer die eigene UID.
 *
 * @param int $uid Benutzer-ID
 * @return int Root-Account-UID
 */
function logbook_db_get_account_root(int $uid): int
{
    global $db;

    if (!$db->field_exists('as_uid', 'users')) {
        return $uid;
    }

    $row    = $db->fetch_array($db->simple_select('users', 'as_uid', 'uid = ' . $uid));
    $as_uid = $row ? (int)$row['as_uid'] : 0;

    return ($as_uid > 0) ? $as_uid : $uid;
}

/**
 * Prüft, ob $actor_uid den Account $target_uid verwalten darf.
 * Erlaubt ist nur der eigene Account und alle über EnhancedAccountSwitcher
 * verknüpften Accounts (gleiche Account-Familie, inkl. Hauptaccount).
 *
 * @param int $actor_uid  Handelnder Benutzer
 * @param int $target_uid Zielaccount
 * @return bool true wenn erlaubt
 */
function logbook_user_can_manage_uid(int $actor_uid, int $target_uid): bool
{
    if ($actor_uid === $target_uid) {
        return true;
    }

    return logbook_db_get_account_root($actor_uid) === logbook_db_get_account_root($target_uid);
}

/**
 * Gibt alle verfügbaren Benutzer zurück (für Dropdown).
 *
 * @return array Benutzer-Liste
 */
function logbook_db_get_all_users(): array
{
    global $db;

    $result = $db->query("SELECT uid, username FROM " . TABLE_PREFIX . "users ORDER BY username");

    $data = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $data[] = $row;
        }
    }

    return $data;
}

/**
 * Prüft, ob ein Benutzer an einer Szene teilnimmt.
 *
 * @param int $scene_id Szene-ID
 * @param int $user_id  Benutzer-ID
 * @return bool true wenn Teilnehmer
 */
function logbook_db_is_participant(int $scene_id, int $user_id): bool
{
    global $db;

    $result = $db->query("
        SELECT COUNT(*) AS participates
        FROM " . TABLE_PREFIX . "logbook_entry
        JOIN " . TABLE_PREFIX . "logbook ON logbook_id = " . TABLE_PREFIX . "logbook.id
        WHERE scene_id = " . $scene_id . "
          AND user_id = " . $user_id . "
    ");

    if ($result) {
        $row = $db->fetch_array($result);
        return (int)($row['participates'] ?? 0) > 0;
    }

    return false;
}

/**
 * Prüft ob das EnhancedAccountSwitcher-Plugin aktiv ist.
 * Wird genutzt um bedingt Account-Sharing-Logik zu aktivieren.
 *
 * @return bool
 */
function logbook_is_accountswitcher_active(): bool
{
    global $db, $cache;
    // Primary check: the as_uid column exists (added by EnhancedAccountSwitcher).
    // Fallback: plugin cache lookup with the known cache key.
    if ($db->field_exists('as_uid', 'users')) {
        return true;
    }
    $plugins = $cache->read('plugins');
    return isset($plugins['active']['enhancedaccountswitcher']);
}

/**
 * Gibt einen Logbook-Eintrag anhand von Logbook-ID und Szene-ID zurück.
 *
 * @param int $logbook_id Logbook-ID
 * @param int $scene_id   Szene-ID
 * @return array|null Eintrag oder null wenn nicht gefunden
 */
function logbook_db_get_entry(int $logbook_id, int $scene_id): ?array
{
    global $db;

    $result = $db->query("
        SELECT * FROM " . TABLE_PREFIX . "logbook_entry
        WHERE scene_id   = " . $scene_id . "
          AND logbook_id = " . $logbook_id . "
    ");

    if ($result) {
        $row = $db->fetch_array($result);
        return $row ?: null;
    }

    return null;
}

/**
 * Gibt die Thread-ID einer Szene zurück.
 *
 * @param int $scene_id Szene-ID
 * @return int Thread-ID oder -1 wenn nicht gefunden
 */
function logbook_db_get_tid_by_scene(int $scene_id): int
{
    global $db;

    $result = $db->query("SELECT tid FROM " . TABLE_PREFIX . "scene WHERE id = " . $scene_id);
    if ($result) {
        $row = $db->fetch_array($result);
        return $row ? (int)$row['tid'] : -1;
    }

    return -1;
}

/**
 * Gibt den Benutzernamen für eine Benutzer-ID zurück.
 *
 * @param int $user_id Benutzer-ID
 * @return string|null Benutzername oder null wenn nicht gefunden
 */
function logbook_db_get_username(int $user_id): ?string
{
    global $db;

    $result = $db->query("SELECT username FROM " . TABLE_PREFIX . "users WHERE uid = " . $user_id);
    if ($result) {
        $row = $db->fetch_array($result);
        return $row ? $row['username'] : null;
    }

    return null;
}

/**
 * Gibt die Benutzer-ID zu einem Benutzernamen zurück (case-insensitive).
 * Wird für die PN-artige Teilnehmer-Autovervollständigung beim Thread-Erstellen
 * benötigt: der Client sendet Namen, die hier zu UIDs aufgelöst werden.
 *
 * @param string $username Benutzername
 * @return int UID oder 0 wenn nicht gefunden
 */
function logbook_db_get_uid_by_username(string $username): int
{
    global $db;

    $username = trim($username);
    if ($username === '') {
        return 0;
    }

    $result = $db->simple_select(
        'users',
        'uid',
        "LOWER(username) = LOWER('" . $db->escape_string($username) . "')",
        ['limit' => 1]
    );
    $row = $db->fetch_array($result);

    return $row ? (int)$row['uid'] : 0;
}

/**
 * Gibt die numerischen IDs aller Popup-View-Templates zurück.
 *
 * @return array Liste der IDs (als String)
 */
function logbook_db_get_popup_template_ids(): array
{
    global $db;

    $result = $db->query("
        SELECT DISTINCT title
        FROM " . TABLE_PREFIX . "templates
        WHERE title LIKE 'logbook\_popup\_%\_view'
    ");

    $ids = [];
    if ($result) {
        while ($row = $db->fetch_array($result)) {
            $id = str_replace(['logbook_popup_', '_view'], '', $row['title']);
            if (is_numeric($id)) {
                $ids[] = $id;
            }
        }
    }

    return $ids;
}

/**
 * Gibt die Forum-ID des Inplay-Archivs zurück.
 * Bevorzugt das Plugin-Setting logbook_archive_forum (self-contained, funktioniert
 * auf jedem Forum). Als abwärtskompatibler Fallback wird die Legacy-Tabelle
 * config gelesen – nur wenn sie existiert, damit Standardforen ohne diese
 * Custom-Tabelle keinen fatalen DB-Fehler bekommen.
 *
 * @return int Forum-ID oder -1 wenn keines konfiguriert ist
 */
function logbook_db_get_archive_fid(): int
{
    global $db, $mybb;

    // Preferred: plugin setting (forumselect may hold a comma list, take the first fid).
    $raw = isset($mybb->settings['logbook_archive_forum'])
        ? trim((string)$mybb->settings['logbook_archive_forum'])
        : '';
    if ($raw !== '') {
        $fids = array_values(array_filter(array_map('intval', explode(',', $raw))));
        if (!empty($fids) && $fids[0] > 0) {
            return $fids[0];
        }
    }

    // Backward-compatible fallback: legacy custom `config` table (only if present).
    if ($db->table_exists('config')) {
        $result = $db->query("
            SELECT wert FROM " . TABLE_PREFIX . "config WHERE name = 'inplay_archive'
        ");
        if ($result) {
            $row = $db->fetch_array($result);
            if ($row) {
                return (int)$row['wert'];
            }
        }
    }

    return -1;
}

/**
 * Gibt die konfigurierten Inplay-Forum-IDs als Array zurück.
 * Liest aus dem Plugin-Setting logbook_inplay_forums.
 *
 * @return int[]
 */
function logbook_get_inplay_fids(): array
{
    global $mybb;

    $raw = isset($mybb->settings['logbook_inplay_forums'])
        ? trim((string)$mybb->settings['logbook_inplay_forums'])
        : '';

    if ($raw === '') {
        return [];
    }

    return array_values(array_filter(array_map('intval', explode(',', $raw))));
}

/**
 * Prüft, ob ein Forum als Inplay-Forum konfiguriert ist.
 * Liest aus dem Plugin-Setting logbook_inplay_forums.
 * Wert -1 (= "Alle Foren" im forumselect) → immer true.
 *
 * @param int $fid Forum-ID
 * @return bool true wenn Inplay-Forum
 */
function logbook_is_forum_inplay(int $fid): bool
{
    global $mybb;

    $raw = isset($mybb->settings['logbook_inplay_forums'])
        ? trim((string)$mybb->settings['logbook_inplay_forums'])
        : '';

    if ($raw === '-1') {
        return true;
    }

    return in_array($fid, logbook_get_inplay_fids(), true);
}

/**
 * Prüft, ob ein Thread in einem Inplay-Forum liegt.
 * Liest die Forum-ID aus der DB und prüft dann gegen das Setting.
 *
 * @param int $tid Thread-ID
 * @return bool true wenn Inplay-Thread
 */
function logbook_is_thread_inplay(int $tid): bool
{
    global $db;

    $result = $db->query("SELECT fid FROM " . TABLE_PREFIX . "threads WHERE tid = " . $tid);
    if ($result) {
        $row = $db->fetch_array($result);
        if ($row) {
            return logbook_is_forum_inplay((int)$row['fid']);
        }
    }

    return false;
}

/**
 * Prüft ob der aktuelle Benutzer das Logbook nutzen darf.
 * Basiert auf dem Plugin-Setting logbook_allowed_groups.
 * Leeres Setting oder Wert -1 (= "Alle Gruppen") → immer true.
 *
 * @return bool
 */
function logbook_user_can_use(): bool
{
    global $mybb;

    $raw = isset($mybb->settings['logbook_allowed_groups'])
        ? trim((string)$mybb->settings['logbook_allowed_groups'])
        : '';

    if ($raw === '' || $raw === '-1') {
        return true;
    }

    $allowed = array_filter(array_map('intval', explode(',', $raw)));
    $user_groups = [(int)$mybb->user['usergroup']];

    if (!empty($mybb->user['additionalgroups'])) {
        $additional = array_filter(array_map('intval', explode(',', $mybb->user['additionalgroups'])));
        $user_groups = array_merge($user_groups, $additional);
    }

    return count(array_intersect($user_groups, $allowed)) > 0;
}

// =============================================================================
// DB-Funktionen – UPDATE
// =============================================================================

/**
 * Aktualisiert ein Logbook anhand der Benutzer-ID.
 * Nur übergebene Felder werden aktualisiert.
 *
 * @param array $data    Felder: description, template_id
 * @param int   $user_id Benutzer-ID
 * @return void
 */
function logbook_db_logbook_update(array $data, int $user_id): void
{
    global $db;

    $update = [];
    if (isset($data['description'])) {
        $update['description'] = $db->escape_string($data['description']);
    }
    if (isset($data['template_id'])) {
        $update['template_id'] = (int)$data['template_id'];
    }
    if (array_key_exists('reminder_days', $data)) {
        $update['reminder_days'] = max(0, (int)$data['reminder_days']);
    }

    if (!empty($update)) {
        $db->update_query('logbook', $update, 'user_id = ' . $user_id);
    }
}

/**
 * Aktualisiert die Daten einer Szene.
 *
 * @param int      $scene_id Szene-ID
 * @param int      $tid      Thread-ID
 * @param string   $ort      Ort
 * @param int      $jahr     Jahr
 * @param int      $monat    Monat
 * @param int      $tag      Tag
 * @param int      $time     Tageszeit
 * @return void
 */
function logbook_db_scene_update(
    int $scene_id,
    int $tid,
    string $ort,
    int $jahr,
    int $monat,
    int $tag,
    int $time,
    int $free_order = 0,
    int $scene_type = 0,
    int $visibility = 1,
    string $title = ''
): void {
    global $db;

    $data = [
        'tid'        => $tid,
        'free_order' => $free_order,
        'ort'        => $db->escape_string($ort),
        'jahr'       => $jahr,
        'monat'      => $monat,
        'tag'        => $tag,
        'time'       => $time,
        'scene_type' => $scene_type,
        'visibility'  => $visibility,
        'title'      => $db->escape_string($title),
    ];

    $db->update_query('scene', $data, 'id = ' . $scene_id);
}

/**
 * Aktualisiert den Text eines Logbook-Eintrags.
 *
 * @param int    $logbook_id Logbook-ID
 * @param int    $scene_id   Szene-ID
 * @param string $text       Beschreibungstext
 * @return void
 */
function logbook_db_entry_update(
    int $logbook_id,
    int $scene_id,
    string $text
): void {
    global $db;

    $db->update_query(
        'logbook_entry',
        ['text' => $db->escape_string($text)],
        'scene_id = ' . $scene_id . ' AND logbook_id = ' . $logbook_id
    );
}

/**
 * Aktualisiert reminder_days eines Logbook-Eintrags (per scene/character override).
 * Wird über user_id + scene_id adressiert, analog zu logbook_db_entry_update().
 *
 * @param int $user_id      Benutzer-ID
 * @param int $scene_id     Szene-ID
 * @param int $reminder_days Tage (0 = Charakter-/ACP-Standardwert verwenden)
 * @return void
 */
function logbook_db_entry_update_reminder(int $user_id, int $scene_id, int $reminder_days): void
{
    global $db;

    $logbook = logbook_db_get_logbook($user_id);
    if (!$logbook) {
        return;
    }

    $db->update_query(
        'logbook_entry',
        ['reminder_days' => max(0, $reminder_days)],
        'scene_id = ' . $scene_id . ' AND logbook_id = ' . (int)$logbook['id']
    );
}

/**
 * Toggles the finished status of the calling user's logbook entry for a scene.
 *
 * @param int $scene_id Scene ID
 * @param int $user_id  User ID
 * @return void
 */
function logbook_db_toggle_finished(int $scene_id, int $user_id): void
{
    global $db;

    $db->query("
        UPDATE " . TABLE_PREFIX . "logbook_entry
        SET finished = NOT finished
        WHERE scene_id = " . $scene_id . "
          AND logbook_id = (
            SELECT id FROM " . TABLE_PREFIX . "logbook WHERE user_id = " . $user_id . "
          )
    ");
}

/**
 * Returns true when every logbook entry for a scene is marked finished.
 *
 * @param int $scene_id Scene ID
 * @return bool
 */
function logbook_db_all_entries_finished(int $scene_id): bool
{
    global $db;

    $result = $db->query("
        SELECT COUNT(*) AS total,
               SUM(finished) AS done
        FROM " . TABLE_PREFIX . "logbook_entry
        WHERE scene_id = " . $scene_id . "
    ");

    if (!$result) {
        return false;
    }

    $row = $db->fetch_array($result);
    return $row && (int)$row['total'] > 0 && (int)$row['total'] === (int)$row['done'];
}

/**
 * Sets the finished status of a single logbook entry (per user/scene).
 *
 * @param int $user_id  User ID
 * @param int $scene_id Scene ID
 * @param int $finished 0 = active, 1 = finished
 * @return void
 */
function logbook_db_entry_update_finished(int $user_id, int $scene_id, int $finished): void
{
    global $db;

    $logbook = logbook_db_get_logbook($user_id);
    if (!$logbook) {
        return;
    }

    $db->update_query(
        'logbook_entry',
        ['finished' => ($finished ? 1 : 0)],
        'scene_id = ' . $scene_id . ' AND logbook_id = ' . (int)$logbook['id']
    );
}

/**
 * Verschiebt einen Thread in ein anderes Forum.
 *
 * @param int $tid    Thread-ID
 * @param int $fid    Ziel-Forum-ID
 * @return void
 */
function logbook_db_move_thread(int $tid, int $fid): void
{
    global $db;

    $db->query("
        UPDATE " . TABLE_PREFIX . "threads
        SET fid = " . $fid . "
        WHERE tid = " . $tid . "
    ");
}

// =============================================================================
// DB-Funktionen – DELETE
// =============================================================================

/**
 * Löscht einen Logbook-Eintrag, wenn er dem angegebenen Benutzer gehört.
 *
 * @param int $user_id  Benutzer-ID (Eigentümerprüfung via Logbook)
 * @param int $entry_id Eintrag-ID
 * @return void
 */
function logbook_db_delete_entry(int $user_id, int $entry_id): void
{
    global $db;

    $db->query("
        DELETE e
        FROM " . TABLE_PREFIX . "logbook_entry e
        LEFT JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        WHERE e.id = " . $entry_id . "
          AND l.user_id = " . $user_id . "
    ");
}

// =============================================================================
// Hilfsfunktionen
// =============================================================================

/**
 * Gibt den Monatsnamen für eine Monatszahl zurück.
 *
 * @param int $monat Monat (1–12)
 * @return string Monatsname
 */
function logbook_get_month(int $monat): string
{
    global $lang;

    $key = 'logbook_month_' . $monat;
    return isset($lang->$key) ? $lang->$key : $lang->logbook_time_unknown;
}

/**
 * Gibt die Tageszeit als lesbaren String zurück.
 *
 * @param int $zeit Tageszeit (1–5)
 * @return string
 */
function logbook_get_time(int $zeit): string
{
    global $lang;

    $map = [
        1 => 'logbook_time_morning',
        2 => 'logbook_time_noon',
        3 => 'logbook_time_afternoon',
        4 => 'logbook_time_evening',
        5 => 'logbook_time_night',
    ];

    $key = $map[$zeit] ?? 'logbook_time_unknown';
    return $lang->$key ?? $lang->logbook_time_unknown;
}

/**
 * Gibt die Teilnehmer einer Szene als kommagetrennten String zurück.
 *
 * @param int $scene_id Szene-ID
 * @return string Teilnehmer-String
 */
function logbook_get_participant_string(int $scene_id): string
{
    global $lang;

    $users = logbook_db_get_participants($scene_id);

    if (empty($users)) {
        return $lang->logbook_no_participants;
    }

    $active  = [];
    $former  = [];
    foreach ($users as $user) {
        $name = htmlspecialchars_uni($user['username']);
        if ((int)$user['finished'] === 1) {
            $former[] = '<span class="lb-participant-former">('
                        . $lang->logbook_participant_former . ' ' . $name
                        . ')</span>';
        } else {
            $active[] = $name;
        }
    }

    return implode(', ', array_merge($active, $former));
}

/**
 * Gibt die Teilnehmer einer Szene als HTML-String für Popup-Ansichten zurück.
 *
 * @param int $scene_id    Szene-ID
 * @param int $template_id Template-ID für das Popup-Layout
 * @return string HTML-String
 */
function logbook_get_participant_popup_string(
    int $scene_id,
    int $template_id
): string {
    global $templates, $lang;

    $users = logbook_db_get_participants($scene_id);

    if (empty($users)) {
        return $lang->logbook_no_participants;
    }

    $userString = '';
    foreach ($users as $user) {
        $username = htmlspecialchars_uni($user['username']);
        if ((int)$user['finished'] === 1) {
            $username = '(' . $lang->logbook_participant_former . ' ' . $username . ')';
        }
        eval("\$userString .= \"" . $templates->get('logbook_popup_' . $template_id . '_userlist') . "\";");
    }

    return $userString;
}

/**
 * Gibt alle verfügbaren Benutzer als HTML-Options-String zurück.
 * Erster Eintrag ist ein "Bitte auswählen"-Platzhalter.
 *
 * @return string HTML für <select>
 */
function logbook_get_users_options(bool $with_placeholder = true): string
{
    global $templates, $lang;

    $optionsString = '';

    if ($with_placeholder) {
        $userId   = 0;
        $username = $lang->logbook_select_user;
        eval("\$optionsString = \"" . $templates->get('logbook_scene_user_row') . "\";");
    }

    foreach (logbook_db_get_all_users() as $user) {
        $userId   = (int)$user['uid'];
        $username = htmlspecialchars_uni($user['username']);
        eval("\$optionsString .= \"" . $templates->get('logbook_scene_user_row') . "\";");
    }

    return $optionsString;
}

/**
 * Gibt einen HTML-Link zum Thread zurück.
 *
 * @param int $tid Thread-ID
 * @return string HTML-Anker
 */
function logbook_get_thread_link(int $tid, string $scene_title = ''): string
{
    global $db, $lang;

    if ($tid <= 0) {
        return $scene_title !== ''
            ? htmlspecialchars_uni($scene_title)
            : '<em>' . $lang->logbook_no_thread . '</em>';
    }

    $result  = $db->simple_select('threads', 'subject', 'tid = ' . $tid);
    $subject = $db->fetch_field($result, 'subject');

    return '<a href="showthread.php?tid=' . $tid . '">'
           . htmlspecialchars_uni((string)$subject) . '</a>';
}

/**
 * Gibt ein Badge-HTML für einen Szenentyp zurück.
 *
 * @param int $scene_type 0=normal, 1=AU, 2=unplayed, 3=planned
 * @return string HTML-Badge oder leer für normalen Typ
 */
function logbook_get_scene_type_badge(int $scene_type): string
{
    global $lang;

    switch ($scene_type) {
        case 1: return '<span class="lb-badge lb-badge-au">'      . $lang->logbook_badge_au      . '</span>';
        case 2: return '<span class="lb-badge lb-badge-unplayed">' . $lang->logbook_badge_unplayed . '</span>';
        case 3: return '<span class="lb-badge lb-badge-planned">'  . $lang->logbook_badge_planned  . '</span>';
        default: return '';
    }
}

// =============================================================================
// Business-Logik
// =============================================================================

/**
 * Erstellt ein Logbook für den Benutzer, falls noch keines existiert.
 *
 * @param int $user_id Benutzer-ID
 * @return void
 */
function logbook_ensure_logbook(int $user_id): void
{
    if (!logbook_db_get_logbook($user_id)) {
        logbook_db_logbook_create('', $user_id, 1);
    }
}

/**
 * Erstellt eine neue Szene sowie alle zugehörigen Logbook-Einträge.
 *
 * @param array $data  Szenen-Daten: tid, ort, jahr, monat, tag, zeit, finished
 * @param array $users Teilnehmer-Array aus POST (user[n][userId], [text], [accepted])
 * @return void
 */
function logbook_save_scene(array $data, array $users): void
{
    global $plugins;

    $scene = logbook_db_scene_create(
        (int)($data['tid']        ?? 0),
        (string)($data['ort']     ?? ''),
        (int)($data['jahr']       ?? 0),
        (int)($data['monat']      ?? 0),
        (int)($data['tag']        ?? 0),
        (int)($data['zeit']       ?? 0),
        (int)($data['free_order'] ?? 0),
        (int)($data['scene_type'] ?? 0),
        (int)($data['visibility'] ?? 1),
        (string)($data['title']   ?? '')
    );

    if (!$scene) {
        return;
    }

    foreach ($users as $user) {
        $uid = (int)($user['userId'] ?? 0);
        if ($uid === 0) {
            continue;
        }

        logbook_ensure_logbook($uid);
        $logbook_id = logbook_db_get_logbook_id($uid);
        if (!$logbook_id) {
            continue;
        }

        logbook_db_entry_create(
            (int)$scene['id'],
            $logbook_id,
            '',
            1,
            (string)($user['text'] ?? '')
        );
    }

    // Hook: fires after a new scene is fully saved with all entries.
    // $hook_data['scene']        – the scene array (id, tid, ort, jahr, monat, tag, …)
    // $hook_data['participants'] – array of ['userId' => int] entries
    $hook_data = [
        'scene'        => $scene,
        'participants' => $users,
    ];
    $plugins->run_hooks('logbook_scene_created', $hook_data);
}

/**
 * Fügt den aktuell eingeloggten Benutzer zu einer Szene hinzu.
 *
 * @param int    $scene_id Szene-ID
 * @param string $text     Beschreibungstext
 * @return void
 */
function logbook_add_user_to_scene(int $tid, string $text): void
{
    global $mybb;

    $uid        = (int)$mybb->user['uid'];
    $logbook_id = logbook_db_get_logbook_id($uid);
    if (!$logbook_id) {
        return;
    }

    // The form asks for the Thread-ID of the scene, so resolve the scene via tid.
    $scene = logbook_db_get_scene_by_tid($tid);
    if (!$scene) {
        return;
    }

    // Don't create a duplicate entry if the user already participates.
    if (logbook_db_is_participant((int)$scene['id'], $uid)) {
        return;
    }

    logbook_db_entry_create((int)$scene['id'], $logbook_id, '', 1, $text);
}

/**
 * Aktualisiert den Text eines Logbook-Eintrags.
 *
 * @param int    $user_id  Benutzer-ID
 * @param int    $scene_id Szene-ID
 * @param string $text     Beschreibungstext
 * @return void
 */
function logbook_update_entry(
    int $user_id,
    int $scene_id,
    string $text
): void {
    $logbook = logbook_db_get_logbook($user_id);
    if (!$logbook) {
        return;
    }

    logbook_db_entry_update((int)$logbook['id'], $scene_id, $text);
}

/**
 * Toggles the finished status of a user's entry and moves the thread to the
 * archive forum once every participant has marked the scene finished.
 *
 * @param int $scene_id Scene ID
 * @param int $user_id  User who is toggling the status
 * @return void
 */
function logbook_change_scene_finished(int $scene_id, int $user_id): void
{
    global $plugins;

    logbook_db_toggle_finished($scene_id, $user_id);

    // Move thread to archive only when all participants have marked the scene finished
    if (logbook_db_all_entries_finished($scene_id)) {
        $fid = logbook_db_get_archive_fid();
        $tid = logbook_db_get_tid_by_scene($scene_id);

        if ($fid > 0 && $tid > 0) {
            logbook_db_move_thread($tid, $fid);
        }
    }

    // Hook: fires after a user's entry finished status has been toggled.
    // $hook_data['scene_id'] – scene ID
    // $hook_data['user_id']  – user who changed the status
    // $hook_data['scene']    – full scene array (finished is now per-entry, not on scene)
    $hook_data = [
        'scene_id' => $scene_id,
        'user_id'  => $user_id,
        'scene'    => logbook_db_get_scene($scene_id),
    ];
    $plugins->run_hooks('logbook_scene_finished_changed', $hook_data);
}

// =============================================================================
// Posting-Reminder-Logik
// =============================================================================

/**
 * Ermittelt den effektiven Reminder-Schwellenwert in Tagen für eine Szene/Charakter-Kombination.
 * Priorität: Szenen-Eintrag > Charakter-Logbook > ACP-Setting.
 * 0 bedeutet auf jeder Ebene "von übergeordneter Ebene erben" (bzw. "deaktiviert" auf ACP-Ebene).
 *
 * @param int $entry_reminder_days   Wert aus logbook_entry.reminder_days
 * @param int $logbook_reminder_days Wert aus logbook.reminder_days
 * @param int $acp_reminder_days     Wert aus ACP-Setting logbook_reminder_days
 * @return int Effektive Anzahl Tage (0 = deaktiviert)
 */
function logbook_get_effective_reminder_days(
    int $entry_reminder_days,
    int $logbook_reminder_days,
    int $acp_reminder_days
): int {
    if ($entry_reminder_days > 0) {
        return $entry_reminder_days;
    }
    if ($logbook_reminder_days > 0) {
        return $logbook_reminder_days;
    }
    return $acp_reminder_days;
}

/**
 * Gibt alle aktiven Szenen zurück, in denen der Benutzer dran ist und seit mindestens
 * X Tagen kein Post im Thread gemacht wurde (Posting-Erinnerung).
 * Free-Order-Szenen werden übersprungen (kein fester Zug).
 *
 * @param int $uid Benutzer-ID
 * @return array Jeder Eintrag: scene_id, tid, scene_subject, days_since_post
 */
function logbook_db_get_overdue_reminder_scenes(int $uid): array
{
    global $db, $mybb;

    $acp_days = (int)($mybb->settings['logbook_reminder_days'] ?? 0);

    $result = $db->query("
        SELECT
            s.id AS scene_id,
            s.tid,
            s.free_order,
            COALESCE(t.subject, s.title, '') AS scene_subject,
            e.id AS entry_id,
            e.reminder_days AS entry_reminder_days,
            l.reminder_days AS logbook_reminder_days,
            COALESCE(MAX(p.dateline), t.dateline, 0) AS last_activity
        FROM " . TABLE_PREFIX . "logbook_entry e
        JOIN " . TABLE_PREFIX . "logbook l ON e.logbook_id = l.id
        JOIN " . TABLE_PREFIX . "scene s ON e.scene_id = s.id
        LEFT JOIN " . TABLE_PREFIX . "threads t ON t.tid = s.tid
        LEFT JOIN " . TABLE_PREFIX . "posts p ON p.tid = s.tid
        WHERE l.user_id = " . $uid . "
          AND e.finished = 0
          AND s.scene_type IN (0, 1)
          AND s.tid > 0
        GROUP BY s.id, s.tid, s.free_order, t.subject, s.title, e.id, e.reminder_days, l.reminder_days, t.dateline
    ");

    if (!$result) {
        return [];
    }

    $overdue = [];
    $now     = TIME_NOW;

    while ($row = $db->fetch_array($result)) {
        $effective_days = logbook_get_effective_reminder_days(
            (int)$row['entry_reminder_days'],
            (int)$row['logbook_reminder_days'],
            $acp_days
        );

        if ($effective_days <= 0) {
            continue;
        }

        // Free-order scenes have no fixed turn, skip them
        if (!empty($row['free_order'])) {
            continue;
        }

        // Only remind if it's actually the user's turn
        $next_poster = logbook_db_get_scene_next_poster((int)$row['scene_id'], (int)$row['tid']);
        if ($next_poster === null || (int)$next_poster['user_id'] !== $uid) {
            continue;
        }

        // Measure days since the last post by anyone in the thread
        $days_since = (int)floor(($now - (int)$row['last_activity']) / 86400);

        if ($days_since >= $effective_days) {
            $row['days_since_post'] = $days_since;
            $overdue[] = $row;
        }
    }

    return $overdue;
}

// =============================================================================
// Public API – aggregierte Statistiken für externe Plugins
// =============================================================================

/**
 * Gibt das vollständige Logbook eines Benutzers zurück – alle Szenen-Einträge
 * mit zugehörigen Szenen-Daten, sortiert nach Datum absteigend.
 * Feuert den Hook 'logbook_user_logbook', damit andere Plugins die Daten
 * filtern, sortieren oder mit eigenen Feldern anreichern können.
 *
 * Jeder Eintrag im Rückgabe-Array enthält:
 *   Alle Felder aus logbook_entry (eid, scene_id, accepted, logbook_id, text, name, reminder_days)
 *   Alle Felder aus scene (id/scene_id, tid, free_order, ort, jahr, monat, tag, time,
 *                          scene_type, visibility, title)
 *   finished – kommt jetzt aus logbook_entry (per-user)
 *
 * $hook_data-Felder:
 *   uid     – Benutzer-ID
 *   entries – Array der Logbook-Einträge (by reference, änderbar)
 *
 * @param int $uid Benutzer-ID
 * @return array Logbook-Einträge (leer wenn kein Logbook vorhanden)
 */
function logbook_get_user_logbook(int $uid): array
{
    global $plugins;

    $entries = logbook_db_get_complete_logbook($uid);

    $hook_data = [
        'uid'     => $uid,
        'entries' => &$entries,
    ];
    $plugins->run_hooks('logbook_user_logbook', $hook_data);

    return $entries;
}

/**
 * Gibt aggregierte Szenen-Statistiken für einen Benutzer zurück.
 * Feuert den Hook 'logbook_user_stats', damit andere Plugins die Daten
 * erweitern oder auslesen können, ohne eigene DB-Queries schreiben zu müssen.
 *
 * Rückgabe-Array (Felder können durch den Hook erweitert werden):
 *   uid      – Benutzer-ID
 *   total    – Gesamtanzahl Szenen
 *   active   – aktive (nicht abgeschlossene) Szenen
 *   finished – abgeschlossene Szenen
 *   overdue  – Szenen, in denen der Benutzer überfällig ist (Anzahl)
 *
 * @param int $uid Benutzer-ID
 * @return array Statistik-Array
 */
function logbook_get_user_stats(int $uid): array
{
    global $plugins;

    $stats = [
        'uid'      => $uid,
        'total'    => logbook_db_get_scene_count($uid),
        'active'   => logbook_db_get_scene_unfinished_count($uid),
        'finished' => logbook_db_get_scene_finished_count($uid),
        'overdue'  => count(logbook_db_get_overdue_reminder_scenes($uid)),
    ];

    // Hook: other plugins can read or add to $stats.
    // Modify $stats directly inside the hook function (passed by reference).
    $plugins->run_hooks('logbook_user_stats', $stats);

    return $stats;
}

/**
 * Rendert den Charakter-Block für die my_scenes-Seite.
 * Kapselt den ~150-Zeilen-Loop aus logbook.php, damit die Frontend-Datei schlank bleibt.
 *
 * @param int[]  $linked_uids      UIDs der verknüpften Charaktere
 * @param int[]  $all_logbook_ids  Map uid → logbook_id
 * @param int    $char_uid_viewer  UID des betrachtenden Users (für "Du bist dran"-Text)
 * @param bool   $viewing_own      true wenn der Viewer seinen eigenen Account sieht
 * @param string $filter_category  '' | '0' | '1'
 * @param int    $filter_days      Mindestalter des letzten Posts in Tagen (0 = kein Filter)
 * @param string $filter_month     'YYYY-M' oder '' für keinen Filter
 * @param int    $filter_open      1 = nur Szenen wo der Charakter dran ist
 * @param string $sort             '' | 'lastpost_asc' | 'lastpost_desc' | 'date_asc' | 'date_desc'
 * @return array {
 *   characters:        string  – fertiges HTML aller Charakter-Blöcke
 *   total_open_scenes: int
 *   total_all_scenes:  int
 *   total_min_open:    int
 *   total_max_open:    int
 * }
 */
function logbook_render_my_scenes_characters(
    array $linked_uids,
    array $all_logbook_ids,
    int $char_uid_viewer,
    bool $viewing_own,
    string $filter_category,
    int $filter_days,
    string $filter_month,
    int $filter_open,
    string $sort,
    int $no_grouping = 0
): array {
    global $lang, $templates;

    $characters        = '';
    $total_open_scenes = 0;
    $total_all_scenes  = 0;
    $total_min_open    = 0;
    $total_max_open    = 0;
    $flat_rows         = [];

    // Flat mode without explicit sort defaults to oldest last-post first
    $effective_sort = ($no_grouping && $sort === '') ? 'lastpost_asc' : $sort;

    foreach ($linked_uids as $char_uid) {
        $char_username    = htmlspecialchars_uni((string)logbook_db_get_username($char_uid));
        $logbook_id       = isset($all_logbook_ids[$char_uid]) ? $all_logbook_ids[$char_uid] : 0;
        $char_rows        = '';
        $char_open_count  = 0;
        $char_total_count = 0;
        $char_min_open    = 0;
        $char_max_open    = 0;

        if ($logbook_id) {
            $char_total_count  = logbook_db_get_total_scene_count($logbook_id);
            $total_all_scenes += $char_total_count;
            $scenes            = logbook_db_get_scenes($logbook_id);

            // Skip per-character sort in flat mode (sorted globally below)
            if (!$no_grouping) {
                if ($effective_sort === 'lastpost_asc') {
                    usort($scenes, static function (array $a, array $b): int {
                        return (int)$a['lastpost'] - (int)$b['lastpost'];
                    });
                } elseif ($effective_sort === 'lastpost_desc') {
                    usort($scenes, static function (array $a, array $b): int {
                        return (int)$b['lastpost'] - (int)$a['lastpost'];
                    });
                } elseif ($effective_sort === 'date_asc') {
                    usort($scenes, static function (array $a, array $b): int {
                        $diff = (int)$a['jahr'] - (int)$b['jahr'];
                        if ($diff !== 0) return $diff;
                        $diff = (int)$a['monat'] - (int)$b['monat'];
                        if ($diff !== 0) return $diff;
                        return (int)$a['tag'] - (int)$b['tag'];
                    });
                } elseif ($effective_sort === 'date_desc') {
                    usort($scenes, static function (array $a, array $b): int {
                        $diff = (int)$b['jahr'] - (int)$a['jahr'];
                        if ($diff !== 0) return $diff;
                        $diff = (int)$b['monat'] - (int)$a['monat'];
                        if ($diff !== 0) return $diff;
                        return (int)$b['tag'] - (int)$a['tag'];
                    });
                }
            }

            foreach ($scenes as $entry) {
                $entry_scene_type = (int)($entry['scene_type'] ?? 0);
                if ($entry_scene_type >= 2) {
                    continue;
                }

                if ($filter_category !== '' && (int)$entry['scene_type'] !== (int)$filter_category) {
                    continue;
                }

                $tid = (int)$entry['tid'];

                if ($filter_month !== '') {
                    $fm_parts = explode('-', $filter_month, 2);
                    if (count($fm_parts) === 2
                        && ((int)$entry['jahr'] !== (int)$fm_parts[0]
                            || (int)$entry['monat'] !== (int)$fm_parts[1])) {
                        continue;
                    }
                }

                if ($filter_days > 0 && $tid > 0 && (int)$entry['lastpost'] > 0) {
                    if ((TIME_NOW - (int)$entry['lastpost']) < $filter_days * 86400) {
                        continue;
                    }
                }

                $next_poster = null;
                if ($tid > 0 && empty($entry['free_order'])) {
                    $next_poster = logbook_db_get_scene_next_poster((int)$entry['id'], $tid);
                }

                $is_solo = logbook_scene_count_active_participants((int)$entry['id']) === 1;

                if ($filter_open) {
                    if ($is_solo) {
                        continue;
                    }
                    if ($tid <= 0) {
                        continue;
                    }
                    if (empty($entry['free_order'])
                        && $next_poster !== null
                        && (int)$next_poster['user_id'] !== $char_uid) {
                        continue;
                    }
                }

                if ($is_solo) {
                    $next_up = '<span class="lb-solo-scene">' . $lang->logbook_solo_participant . '</span>';
                } elseif ($tid <= 0) {
                    $next_up = '–';
                } elseif (!empty($entry['free_order'])) {
                    $next_up = '<span class="lb-free-order">' . $lang->logbook_no_fixed_order . '</span>';
                    $char_max_open++;
                } else {
                    if ($next_poster === null) {
                        $next_up = '–';
                        $char_max_open++;
                    } elseif ((int)$next_poster['user_id'] === $char_uid) {
                        $next_up = $viewing_own
                            ? '<span class="lb-your-turn">' . $lang->logbook_you_are_next . '</span>'
                            : '<span class="lb-your-turn">' . $char_username . '</span>';
                        $char_min_open++;
                        $char_max_open++;
                    } else {
                        $next_up = htmlspecialchars_uni($next_poster['username']);
                    }
                }

                $scene_type_badge = logbook_get_scene_type_badge($entry_scene_type);

                if ($tid > 0) {
                    $title = '<a href="showthread.php?tid=' . $tid . '" class="lb-scene-link">'
                             . htmlspecialchars_uni((string)$entry['subject']) . '</a>';
                } elseif (!empty($entry['title'])) {
                    $title = htmlspecialchars_uni((string)$entry['title']);
                } else {
                    $title = '<em class="lb-no-thread">' . $lang->logbook_no_thread . '</em>';
                }

                $tag        = (int)$entry['tag'];
                $monat      = logbook_get_month((int)$entry['monat']);
                $jahr       = (int)$entry['jahr'];
                $ort        = htmlspecialchars_uni((string)($entry['ort'] ?? ''));
                $zeit       = logbook_get_time((int)$entry['time']);
                $teilnehmer = logbook_get_participant_string((int)$entry['id']);

                $lastposter = $tid > 0 ? htmlspecialchars_uni((string)$entry['lastposter']) : '';
                if ($lastposter !== '') {
                    $lastpost_date = $entry['lastpost'] ? date('d.m.Y', (int)$entry['lastpost']) : '';
                    $lastpost_link = 'showthread.php?tid=' . $tid . '&amp;action=newpost';
                    $lastpost_info = '<a href="' . $lastpost_link . '" class="lb-lastpost-link">'
                                     . $lang->logbook_lastpost_by . ' ' . $lastposter . '</a>'
                                     . ($lastpost_date !== '' ? '<br /><em class="lb-scene-time">' . $lastpost_date . '</em>' : '');
                } else {
                    $lastpost_info = '–';
                }

                $row_html = '';
                eval("\$row_html = \"" . $templates->get('logbook_my_scenes_row') . "\";");

                if ($no_grouping) {
                    $flat_rows[] = [
                        'lastpost' => (int)($entry['lastpost'] ?? 0),
                        'jahr'     => (int)$entry['jahr'],
                        'monat'    => (int)$entry['monat'],
                        'tag'      => (int)$entry['tag'],
                        'html'     => $row_html,
                    ];
                } else {
                    $char_rows .= $row_html;
                }
                $char_open_count++;
            }
        }

        $total_open_scenes += $char_open_count;
        $total_min_open    += $char_min_open;
        $total_max_open    += $char_max_open;

        if ($no_grouping) {
            continue;
        }

        if ($char_min_open === $char_max_open) {
            $char_open_label = $lang->sprintf($lang->logbook_my_scenes_open_of_total, $char_min_open, $char_open_count);
        } else {
            $char_open_label = $lang->sprintf($lang->logbook_my_scenes_open_range_of_total, $char_min_open, $char_max_open, $char_open_count);
        }

        if ($char_rows === '') {
            eval("\$char_rows = \"" . $templates->get('logbook_my_scenes_empty') . "\";");
        }

        eval("\$characters .= \"" . $templates->get('logbook_my_scenes_char') . "\";");
    }

    if ($no_grouping) {
        if ($effective_sort === 'lastpost_desc') {
            usort($flat_rows, static function (array $a, array $b): int {
                return $b['lastpost'] - $a['lastpost'];
            });
        } elseif ($effective_sort === 'date_asc') {
            usort($flat_rows, static function (array $a, array $b): int {
                $diff = $a['jahr'] - $b['jahr'];
                if ($diff !== 0) return $diff;
                $diff = $a['monat'] - $b['monat'];
                if ($diff !== 0) return $diff;
                return $a['tag'] - $b['tag'];
            });
        } elseif ($effective_sort === 'date_desc') {
            usort($flat_rows, static function (array $a, array $b): int {
                $diff = $b['jahr'] - $a['jahr'];
                if ($diff !== 0) return $diff;
                $diff = $b['monat'] - $a['monat'];
                if ($diff !== 0) return $diff;
                return $b['tag'] - $a['tag'];
            });
        } else {
            // 'lastpost_asc' (default for flat mode)
            usort($flat_rows, static function (array $a, array $b): int {
                return $a['lastpost'] - $b['lastpost'];
            });
        }

        $flat_html = '';
        foreach ($flat_rows as $r) {
            $flat_html .= $r['html'];
        }
        if ($flat_html === '') {
            eval("\$flat_html = \"" . $templates->get('logbook_my_scenes_empty') . "\";");
        }
        eval("\$characters = \"" . $templates->get('logbook_my_scenes_flat') . "\";");
    }

    return [
        'characters'        => $characters,
        'total_open_scenes' => $total_open_scenes,
        'total_all_scenes'  => $total_all_scenes,
        'total_min_open'    => $total_min_open,
        'total_max_open'    => $total_max_open,
    ];
}

// =============================================================================
// Hook-Handler
// =============================================================================

/**
 * Hook: usercp_menu – Lädt die Sprachdatei, damit der per Template-Injektion
 * eingefügte UserCP-Logbook-Link seine Beschriftung erhält.
 *
 * @return void
 */
function logbook_usercp_nav(): void
{
    global $lang;
    $lang->load('logbook');
}

/**
 * Hook: global_intermediate – Fügt den Logbook-Link in die Header-Navigation ein.
 *
 * @return void
 */
function logbook_handle_header_link(): void
{
    global $mybb, $lang, $templates, $logbook_header_link;

    $logbook_header_link = '';

    if ($mybb->user['uid'] == 0 || !logbook_user_can_use()) {
        return;
    }

    $lang->load('logbook');
    eval('$logbook_header_link = "' . $templates->get('logbook_header_link') . '";');
}

/**
 * Hook: newthread_start – Zeigt das Logbook-Formular beim Erstellen eines neuen
 * Threads in einem Inplay-Forum an.
 *
 * @return void
 */
function logbook_handle_new_reply(): void
{
    global $mybb, $templates, $lang, $logbook_entry_add;

    $logbook_entry_add = '';
    $lang->load('logbook');

    $fid = $mybb->get_input('fid', MyBB::INPUT_INT);
    if (!$fid) {
        return;
    }

    if (logbook_is_forum_inplay($fid) && logbook_user_can_use()) {
        eval("\$logbook_entry_add = \"" . $templates->get("logbook_newreply") . "\";");
    }
}

/**
 * Hook: newthread_do_newthread_end – Speichert Szenen-Daten nach dem Erstellen
 * eines neuen Threads in einem Inplay-Forum.
 *
 * @return void
 */
function logbook_handle_do_new_thread(): void
{
    global $tid, $mybb;

    if (!logbook_is_thread_inplay((int)$tid) || !logbook_user_can_use()) {
        return;
    }

    $scene_data = [
        'tid'        => (int)$tid,
        'ort'        => $mybb->get_input('ort'),
        'jahr'       => $mybb->get_input('jahr',       MyBB::INPUT_INT),
        'monat'      => $mybb->get_input('monat',      MyBB::INPUT_INT),
        'tag'        => $mybb->get_input('tag',        MyBB::INPUT_INT),
        'zeit'       => $mybb->get_input('zeit',       MyBB::INPUT_INT),
        'free_order' => $mybb->get_input('free_order', MyBB::INPUT_INT),
    ];

    $current_uid = (int)$mybb->user['uid'];
    $users = [
        ['userId' => $current_uid, 'text' => ''],
    ];

    // Participants are entered as a comma-separated list of usernames (PM-style
    // autocomplete on the new-thread form); resolve each to a uid.
    $names_raw = (string)$mybb->get_input('participants_names');
    $names     = array_filter(array_map('trim', explode(',', $names_raw)));

    $alert_uids = [];
    $seen       = [$current_uid => true];
    foreach ($names as $name) {
        $uid = logbook_db_get_uid_by_username($name);
        if ($uid <= 0 || isset($seen[$uid])) {
            continue;
        }
        $seen[$uid]   = true;
        $users[]      = ['userId' => $uid, 'text' => ''];
        $alert_uids[] = $uid;
    }

    logbook_save_scene($scene_data, $users);

    if (!empty($alert_uids)) {
        $scene = logbook_db_get_scene_by_tid((int)$tid);
        if ($scene) {
            logbook_send_scene_alerts((int)$scene['id'], (int)$tid, $alert_uids);
        }
    }
}

/**
 * Hook: forumdisplay_thread_end – Ergänzt die Thread-Beschreibung in der
 * Forensübersicht um Szenen-Metadaten (Teilnehmer, Datum, Zeit).
 *
 * @return void
 */
function logbook_handle_thread_display(): void
{
    global $thread, $lang, $plugins;

    $lang->load('logbook');

    if (!logbook_is_thread_inplay((int)$thread['tid'])) {
        return;
    }

    $scene = logbook_db_get_scene_by_tid((int)$thread['tid']);
    if (!$scene) {
        return;
    }

    // Hook: fires after scene data is loaded for a thread-list row.
    // Other plugins can read $scene or modify $thread before the description is built.
    // $hook_data['scene']  – scene array from DB
    // $hook_data['thread'] – the $thread array (by reference, modifications affect display)
    $hook_data = [
        'scene'  => &$scene,
        'thread' => &$thread,
    ];
    $plugins->run_hooks('logbook_scene_thread_display', $hook_data);

    $thread['profilelink'] = '';
    $member   = logbook_get_participant_string((int)$scene['id']);
    $location = htmlspecialchars_uni((string)($scene['ort'] ?? ''));
    $tag      = (int)$scene['tag'];
    $monat    = logbook_get_month((int)$scene['monat']);
    $jahr     = (int)$scene['jahr'];
    $zeit     = logbook_get_time((int)$scene['time']);

    $icon_users    = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><circle cx="5.5" cy="5" r="2.5" stroke="currentColor" stroke-width="1.3"/><path d="M1 13.5c0-2.49 2.01-4.5 4.5-4.5S10 11.01 10 13.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="11.5" cy="5" r="2" stroke="currentColor" stroke-width="1.3"/><path d="M13.5 9.5c1.38.5 2.5 1.8 2.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
    $icon_pin      = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><path d="M8 1.5C5.52 1.5 3.5 3.52 3.5 6c0 3.75 4.5 8.5 4.5 8.5S12.5 9.75 12.5 6C12.5 3.52 10.48 1.5 8 1.5zM8 7.75A1.75 1.75 0 1 1 8 4.25 1.75 1.75 0 0 1 8 7.75z" fill="currentColor"/></svg>';
    $icon_calendar = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><rect x="1.5" y="3" width="13" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/><path d="M1.5 7h13" stroke="currentColor" stroke-width="1.3"/><path d="M5 1.5V4M11 1.5V4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
    $icon_clock    = '<svg class="lb-icon" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/><path d="M8 4.5V8l2.5 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>';

    $scene_type_badge = logbook_get_scene_type_badge((int)($scene['scene_type'] ?? 0));

    $items = '<span class="logbook-thread-meta-item">' . $icon_users . $member . '</span>'
        . ($location !== '' ? '<span class="logbook-thread-meta-item">' . $icon_pin . $location . '</span>' : '')
        . '<span class="logbook-thread-meta-item">' . $icon_calendar . $tag . '. ' . $monat . ' ' . $jahr . '</span>'
        . '<span class="logbook-thread-meta-item">' . $icon_clock . $zeit . '</span>'
        . ($scene_type_badge !== '' ? '<span class="logbook-thread-meta-item logbook-thread-meta-badge">' . $scene_type_badge . '</span>' : '');

    $thread['description'] = '<span class="logbook-thread-meta">' . $items . '</span>';
}

/**
 * Hook: showthread_start – Zeigt eine Szenen-Info-Box zwischen Thread-Titel
 * und erstem Beitrag, wenn der Thread eine Inplay-Szene ist.
 *
 * @return void
 */
function logbook_handle_showthread(): void
{
    global $thread, $templates, $lang, $logbook_scene_info, $plugins;

    $logbook_scene_info = '';
    $lang->load('logbook');

    if (empty($thread['tid']) || !logbook_is_thread_inplay((int)$thread['tid'])) {
        return;
    }

    $scene = logbook_db_get_scene_by_tid((int)$thread['tid']);
    if (!$scene) {
        return;
    }

    // Hook: fires when a thread's scene info box is about to be rendered.
    // Other plugins can read scene data or add their own info via the template variable.
    // $hook_data['scene']  – scene array from DB
    // $hook_data['thread'] – the global $thread array
    $hook_data = [
        'scene'  => &$scene,
        'thread' => &$thread,
    ];
    $plugins->run_hooks('logbook_scene_showthread', $hook_data);

    $scene_participants = logbook_get_participant_string((int)$scene['id']);
    $scene_location     = htmlspecialchars_uni((string)($scene['ort'] ?? ''));
    $scene_day          = (int)$scene['tag'];
    $scene_month        = logbook_get_month((int)$scene['monat']);
    $scene_year         = (int)$scene['jahr'];
    $scene_date         = $scene_day . '. ' . $scene_month . ' ' . $scene_year;
    $scene_daytime      = logbook_get_time((int)$scene['time']);
    $scene_type_badge   = logbook_get_scene_type_badge((int)($scene['scene_type'] ?? 0));

    eval("\$logbook_scene_info = \"" . $templates->get('logbook_scene_info') . "\";");
}

/**
 * Lädt die MyAlerts-Klassen manuell falls noch nicht geschehen (nötig in activate/deactivate).
 *
 * @return bool true wenn Klassen verfügbar
 */
function logbook_load_myalerts_classes(): bool
{
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        return true;
    }

    if (!defined('MYALERTS_PLUGIN_PATH')) {
        define('MYALERTS_PLUGIN_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/MyAlerts');
    }
    if (!defined('MYBBSTUFF_CORE_PATH')) {
        define('MYBBSTUFF_CORE_PATH', MYBB_ROOT . 'inc/plugins/MybbStuff/Core/');
    }

    $loader_path = MYBBSTUFF_CORE_PATH . 'ClassLoader.php';
    if (!is_readable($loader_path)) {
        return false;
    }

    require_once $loader_path;
    $classLoader = new MybbStuff_Core_ClassLoader();
    $classLoader->registerNamespace(
        'MybbStuff_MyAlerts',
        [MYALERTS_PLUGIN_PATH . '/src']
    );
    $classLoader->register();

    return class_exists('MybbStuff_MyAlerts_AlertTypeManager');
}

/**
 * Registriert den MyAlerts-Alert-Typ beim Plugin-Aktivieren.
 */
function logbook_install_alert_type(): void
{
    global $db, $cache;

    if (!$db->table_exists('alert_types')) {
        return;
    }

    if (!logbook_load_myalerts_classes()) {
        return;
    }

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);

    $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
    $alertType->setCode('logbook_scene_invite');
    $alertType->setEnabled(true);
    $alertType->setCanBeUserDisabled(true);

    $alertTypeManager->add($alertType);
}

/**
 * Entfernt den MyAlerts-Alert-Typ beim Plugin-Deaktivieren.
 */
function logbook_uninstall_alert_type(): void
{
    global $db, $cache;

    if (!$db->table_exists('alert_types')) {
        return;
    }

    if (!logbook_load_myalerts_classes()) {
        return;
    }

    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
    $alertTypeManager->deleteByCode('logbook_scene_invite');
}

/**
 * Hook: global_start / xmlhttp – Lädt Sprache und registriert den MyAlerts-Formatter.
 * Sprache wird immer geladen (auch ohne MyAlerts), damit $lang->myalerts_setting_logbook_scene_invite
 * auf alerts.php?action=settings verfügbar ist.
 */
function logbook_register_myalerts_formatter(): void
{
    global $mybb, $lang;

    $lang->load('logbook');

    if (!function_exists('myalerts_info')) {
        return;
    }

    if (!class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') ||
        !class_exists('MybbStuff_MyAlerts_AlertFormatterManager') ||
        class_exists('Logbook_MyAlertsFormatter')
    ) {
        return;
    }

    require_once MYBB_ROOT . 'inc/plugins/logbooks/myalerts_formatter.php';

    $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();
    if (!$formatterManager) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
    }
    if ($formatterManager) {
        $formatterManager->registerFormatter(
            new Logbook_MyAlertsFormatter($mybb, $lang, 'logbook_scene_invite')
        );
    }
}

/**
 * Sendet MyAlerts-Benachrichtigungen an Szenenteilnehmer (außer dem Ersteller).
 *
 * @param int   $scene_id        Szene-ID
 * @param int   $tid             Thread-ID
 * @param int[] $participant_uids UIDs der zu benachrichtigenden Teilnehmer
 */
function logbook_send_scene_alerts(int $scene_id, int $tid, array $participant_uids): void
{
    global $mybb, $db;

    if (!function_exists('myalerts_create_instances')) {
        return;
    }

    $alertManager     = MybbStuff_MyAlerts_AlertManager::getInstance();
    $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

    if (!$alertManager || !$alertTypeManager) {
        return;
    }

    $alertType = $alertTypeManager->getByCode('logbook_scene_invite');
    if (!$alertType) {
        return;
    }

    $result  = $db->simple_select('threads', 'subject', 'tid = ' . $tid);
    $thread  = $db->fetch_array($result);
    $subject = $thread ? $thread['subject'] : '';

    $from_uid = (int)$mybb->user['uid'];

    foreach ($participant_uids as $uid) {
        $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$uid, $alertType, $scene_id);
        $alert->setFromUserId($from_uid);
        $alert->setExtraDetails([
            'tid'     => $tid,
            'subject' => $subject,
        ]);
        $alertManager->addAlert($alert);
    }
}

/**
 * Hook: index_start – Zeigt auf der Startseite Posting-Erinnerungen für überfällige
 * Szenen sowie eine Liste der aktuellen Szenen für den eingeloggten Benutzer.
 *
 * @return void
 */
function logbook_handle_index(): void
{
    global $mybb, $db, $templates, $lang, $logbookAccept, $plugins, $theme;

    $lang->load('logbook');

    if (empty($mybb->user['uid']) || (int)$mybb->user['uid'] <= 0) {
        return;
    }

    $uid        = (int)$mybb->user['uid'];
    $logbook_id = logbook_db_get_logbook_id($uid);

    // Determine which UIDs to collect reminders for.
    // If the current user is the main account (as_uid = 0) and AccountSwitcher is active,
    // show posting reminders for all linked characters.
    // Query the DB directly – $mybb->user['as_uid'] is unreliable across session states.
    $is_main_account = false;
    if (logbook_is_accountswitcher_active()) {
        $as_row          = $db->fetch_array($db->simple_select('users', 'as_uid', 'uid = ' . $uid));
        $is_main_account = !$as_row || (int)$as_row['as_uid'] === 0;
    }

    if ($is_main_account) {
        $reminder_uids = logbook_db_get_linked_user_ids($uid);
    } else {
        $reminder_uids = $logbook_id ? [$uid] : [];
    }

    // Nothing to show if no own logbook and no linked characters to display.
    if (!$logbook_id && empty($reminder_uids)) {
        return;
    }

    // Build reminder bars – grouped by character when showing multiple accounts
    $reminder_html  = '';
    $overdue_scenes = [];
    $reminder_bars  = '';
    $show_char_label = $is_main_account && count($reminder_uids) > 1;

    foreach ($reminder_uids as $reminder_uid) {
        $char_scenes = logbook_db_get_overdue_reminder_scenes($reminder_uid);
        if (empty($char_scenes)) {
            continue;
        }

        if ($show_char_label) {
            $char_user   = get_user($reminder_uid);
            $char_label  = htmlspecialchars_uni((string)($char_user['username'] ?? ''));
            $reminder_bars .= '<div class="lb-reminder-char-label">' . $char_label . '</div>';
        }

        foreach ($char_scenes as $scene) {
            $reminder_tid      = (int)$scene['tid'];
            $reminder_title    = '<a href="showthread.php?tid=' . $reminder_tid . '">'
                               . htmlspecialchars_uni((string)$scene['scene_subject']) . '</a>';
            $reminder_days_ago = $lang->sprintf($lang->logbook_reminder_days_ago, (int)$scene['days_since_post']);
            eval("\$reminder_bars .= \"" . $templates->get('logbook_index_reminder_bar') . "\";");
        }

        $overdue_scenes = array_merge($overdue_scenes, $char_scenes);
    }

    if ($reminder_bars !== '') {
        eval("\$reminder_html = \"" . $templates->get('logbook_index_reminder') . "\";");
    }

    // Active scenes widget – skip for main accounts; they have no RPG character of their own
    $entries     = [];
    $scenes_html = '';
    if ($logbook_id && !$is_main_account) {
        $entries = logbook_db_get_scenes($logbook_id);
        if (!empty($entries)) {
            $items = '';
            foreach ($entries as $entry) {
                $trow       = alt_trow(); // MyBB standard trow1/trow2 alternation
                $widget_tid = (int)$entry['tid'];
                if ($widget_tid > 0) {
                    $title    = "<a href='showthread.php?tid=" . $widget_tid . "'>"
                                . htmlspecialchars_uni($entry['subject']) . "</a>";
                    $lastPost = $lang->logbook_widget_last_post_by
                                . " <a href='member.php?action=profile&uid=" . (int)$entry['lastposteruid'] . "'>"
                                . htmlspecialchars_uni($entry['lastposter']) . "</a> "
                                . $lang->logbook_widget_last_post_on . " "
                                . date("d.m.Y", (int)$entry['lastpost']);
                } else {
                    $title    = logbook_get_scene_type_badge((int)($entry['scene_type'] ?? 0))
                                . ' <em>' . $lang->logbook_no_thread . '</em>';
                    $lastPost = '';
                }
                eval("\$items .= \"" . $templates->get("logbook_index_unaccepted_entry") . "\";");
            }
            eval("\$scenes_html = \"" . $templates->get("logbook_index_unaccepted") . "\";");
        }
    }

    // Hook: fires after all index data is collected, before output is assembled.
    // Other plugins can read or modify $hook_data and append HTML to $logbookAccept.
    // $hook_data['uid']           – current user ID
    // $hook_data['logbook_id']    – logbook ID of the user
    // $hook_data['scenes']        – active scene rows (same as $entries above)
    // $hook_data['overdue']       – overdue scene rows
    // $hook_data['extra_html']    – append HTML here; it will be placed after the scenes widget
    $hook_data = [
        'uid'        => $uid,
        'logbook_id' => $logbook_id,
        'scenes'     => $entries,
        'overdue'    => $overdue_scenes,
        'extra_html' => '',
    ];
    $plugins->run_hooks('logbook_index_data', $hook_data);

    if ($reminder_html !== '' || $scenes_html !== '' || $hook_data['extra_html'] !== '') {
        $logbookAccept = $reminder_html . $scenes_html . $hook_data['extra_html'];
    }
}
