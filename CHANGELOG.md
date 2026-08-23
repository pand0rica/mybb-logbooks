# Changelog

All notable changes to the Logbook plugin are documented here.

## [Unreleased]

### Fixed
- Participants were hidden in all display views (thread view, scene info box, logbook view, popup, "current scenes") and only appeared in the edit view. Cause: the display query filtered `as_uid > 0`, silently dropping any participant that is a main account (`as_uid = 0`) whenever an account-switcher was installed. The display query now returns every recorded participant, consistent with the edit view.

## [2.0.0] – 2026-08-20 · First public release

First public release. Substantially reworked, secured and extended.

### Scenes & logbooks
- Personal logbook per user; scenes are captured automatically when a thread is created in an in-play forum
- Scene types: Normal, AU (alternate universe), Unplayed, Planned
- Visibility setting (public / private) for Unplayed and Planned scenes
- Title field for threadless scenes (Unplayed / Planned)
- Free-order toggle per scene for scenes without a fixed posting order
- Per-participant finished status (`logbook_entry.finished`): a scene only moves to the archive forum once every participant has marked it finished
- Scene type badges shown throughout the UI (logbook view, popup, my_scenes, thread listing)
- Scene metadata block in the forum thread listing (`forumdisplay_thread_end`) and scene info box above posts in thread view (`showthread_start`)
- Logbook link injected into the header navigation (`global_intermediate`)

### my_scenes overview
- `logbook.php?action=my_scenes` — all active scenes across all linked characters
- Filters: by category (Normal / AU), days since last post, in-game month, open scenes only
- Sorting: by last post date and by in-game date (asc/desc)
- "Who is next?" indicator highlighting whose turn it is

### Posting reminders
- Reminder bar on the forum index when it is the user's turn and the last activity exceeds a configurable threshold
- Threshold cascade: per-scene override > per-character override > global ACP default (`logbook_reminder_days`, 0 = disabled)
- Multi-character aggregation: main accounts see reminders for all linked characters, grouped by character name

### Integrations & API
- MyAlerts integration — `logbook_scene_invite` notification when a user is added to a scene
- Optional EnhancedAccountSwitcher support (`users.as_uid`) for linked-account ownership checks
- Public API: `logbook_get_user_logbook(int $uid)`, `logbook_get_user_stats(int $uid)`
- Hooks for other plugins: `logbook_scene_created`, `logbook_scene_finished_changed`, `logbook_scene_thread_display`, `logbook_scene_showthread`, `logbook_index_data`, `logbook_user_logbook`, `logbook_user_stats`

### Configuration
- ACP settings: in-play forums, allowed user groups, global reminder default, archive forum
- Archive forum is configured via a plugin setting; no custom database table required (a legacy `config` table is still honoured as a fallback)
- Settings persist across deactivate/reactivate and are only removed on a full uninstall; reactivation adds any settings introduced in a newer version

### Theming
- CSS registered in the MyBB theme system on activation (all themes) and removed on deactivation
- Styling integrates with the active theme instead of overriding it; only genuine accents (reminder yellow, scene-type badges) keep custom color
- No third-party requests: participant autocomplete uses MyBB's bundled jQuery and its own `xmlhttp.php?action=get_users` endpoint
- Status badges use the theme's semantic colors and read clearly on both light and dark themes

### Security
- All database inputs escaped with `$db->escape_string()`
- `verify_post_check()` on all POST form handlers
- All input via `$mybb->get_input()`; `TABLE_PREFIX` used in all raw queries
