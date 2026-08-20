# Logbook Plugin – Claude-Kontext

## Zweck
RPG-Forum-Plugin für MyBB 1.8. Erlaubt Benutzern, ihre Inplay-Szenen in einem persönlichen
Logbook zu verwalten. Szenen werden beim Erstellen eines Threads in einem Inplay-Forum
automatisch erfasst.

## Besonderheiten

### Abhängigkeiten (optionale Plugins)
- **EnhancedAccountSwitcher** (https://github.com/jnschrag/EnhancedAccountSwitcher):
  Stellt `mybb_users.as_uid` bereit. `logbook_is_accountswitcher_active()` prüft per Plugin-Cache
  ob es aktiv ist; nur dann wird die `as_uid`-Bedingung in `logbook_db_is_owner()` verwendet.
  Plugin-Cache-Key: `enhancedaccountswitcher`.

### Plugin-Settings (MyBB ACP → Konfiguration → Einstellungen → Logbook)
- `logbook_inplay_forums` – kommagetrennte Forum-IDs für Inplay-Foren (ersetzt custom `inplay`-Spalte)
- `logbook_allowed_groups` – kommagetrennte Usergroup-IDs die das Logbook nutzen dürfen; leer = alle

Settings werden in `logbook_activate()` angelegt. Sie **bleiben** bei Deaktivierung erhalten
(damit die Konfiguration ein Reaktivieren übersteht) und werden erst in `logbook_uninstall()`
entfernt. `logbook_activate()` legt die settinggroup nur an, wenn sie noch nicht existiert, und
ergänzt im Else-Zweig fehlende Einzel-Settings (Upgrade-Pfad).

### Benutzerdefinierte DB-Spalten
Keine custom Spalten mehr nötig (außer was andere Plugins hinzufügen).
- `mybb_config.inplay_archive` – Forum-ID des Inplay-Archivs (in einer custom `config`-Tabelle)

`mybb_users.as_uid` wird **nicht** manuell hinzugefügt – kommt vom EnhancedAccountSwitcher.

### Template-System
Templates liegen als `.tpl`-Dateien in `inc/plugins/logbooks/templates/`.
`logbook_activate()` liest alle `.tpl`-Dateien direkt ein und speichert sie in `mybb_templates`.
`logbook_deactivate()` entfernt alle Templates via `DELETE WHERE title LIKE 'logbook\_%'`.

### Frontend-Datei
`logbook.php` liegt im Repo-Root und spiegelt damit die MyBB-Verzeichnisstruktur.
Beim Entpacken des Plugins landet sie automatisch im MyBB-Root – kein manuelles Kopieren nötig.

## Funktionsstruktur (alles in `inc/plugins/logbook.php`)
Kein Klassen-Layer mehr – alle Funktionen sind prozedural mit `logbook_`-Präfix:

- `logbook_db_*()` – DB-Operationen (CREATE/READ/UPDATE/DELETE)
- `logbook_get_*()` – Hilfsfunktionen für Templates/Ausgabe
- `logbook_handle_*()` – Hook-Handler
- `logbook_ensure_logbook()`, `logbook_save_scene()`, `logbook_change_scene_finished()` – Business-Logik
- `logbook_update_entry()`, `logbook_add_user_to_scene()` – Schreiboperationen

## Hooks
| Hook | Funktion |
|------|----------|
| `newthread_start` | `logbook_handle_new_reply()` – Szenen-Formular beim Thread erstellen |
| `newthread_do_newthread_end` | `logbook_handle_do_new_thread()` – Szene speichern |
| `index_start` | `logbook_handle_index()` – Reminder & Szenen-Widget auf Startseite |
| `forumdisplay_thread_end` | `logbook_handle_thread_display()` – Szenen-Metadaten in Thread-Liste |
| `showthread_start` | `logbook_handle_showthread()` – Szenen-Info-Box im Thread |
| `global_start` / `xmlhttp` | `logbook_register_myalerts_formatter()` – MyAlerts-Formatter registrieren |
| `usercp_menu` | `logbook_usercp_nav()` – Sprache laden für UserCP-Nav |
| `global_intermediate` | `logbook_handle_header_link()` – Logbook-Link in Header-Navigation |

## Commit-Konventionen
- Englische Commit-Messages
- Tags: `v2.0.0`, `v2.1.0` etc.
