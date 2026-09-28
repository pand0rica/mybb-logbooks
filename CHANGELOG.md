# Changelog

All notable changes to the Logbook plugin are documented here.

## [Unreleased]

### Changed
- All stylesheet colours can now be overridden by the theme through `--h4w-*` custom properties (e.g. `--h4w-logbook-accent`, `--h4w-logbook-bg-head`, `--h4w-logbook-warn-bg`). Each variable keeps the previous colour as fallback, so themes without these properties look unchanged.

### Added
- Posting reminder bars on the index: text size and colours are themeable via `--h4w-logbook-reminder-fontsize`, `--h4w-logbook-reminder-fontsize-small`, `--h4w-logbook-reminder-title-color`, `--h4w-logbook-reminder-label-color` and `--h4w-logbook-reminder-label-opacity` (defaults: 12px / 11px / inherited colour / 0.75, as before), so dark themes and narrow fonts can stay readable.

## [2.1.0] – 2026-08-27

### Added
- Scene statistics in the postbit and on the member profile. Two new ACP settings (**Postbit Statistics**, **Profile Statistics**) let you choose per location which values are shown: total scenes, active scenes, finished scenes, overdue scenes and in-play posts. "In-play posts" counts a member's visible posts in the configured in-play forums. Both blocks are rendered from templates injected into `postbit`/`postbit_classic` (anchored on `{$post['user_details']}`) and `member_profile` (anchored on `{$signature}`); existing installs must deactivate + reactivate the plugin once to pick up the injection and the new settings. Only members whose group is allowed to use the logbook (setting **Allowed User Groups**) see the blocks.
- `logbook_get_user_stats()` now also returns an `inplayposts` count and caches its result per request.

### Fixed
- Participants were hidden in all display views (thread view, scene info box, logbook view, popup, "current scenes") and only appeared in the edit view. Cause: the display query filtered `as_uid > 0`, silently dropping any participant that is a main account (`as_uid = 0`) whenever an account-switcher was installed. The display query now returns every recorded participant, consistent with the edit view.
- Scene metadata (participants, location, date, time) did not appear in the thread listing on stock themes. Cause: the strip was written to `$thread['description']`, which stock MyBB only renders inside the thread link's `title` tooltip, not as visible markup — it only showed on themes whose `forumdisplay_thread` template had been customised. The strip is now written to a dedicated `$thread['logbook_meta']` variable that the plugin injects into `forumdisplay_thread` on activation, so it renders on any theme. Existing installs must deactivate + reactivate the plugin once to pick up the injection.

### Changed
- The thread listing no longer overwrites `$thread['description']` / blanks `$thread['profilelink']`; the real thread description and author link are left intact.

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
