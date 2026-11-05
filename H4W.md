# h4w-Anpassungen

Dieser Branch ist `main` **plus** die Anpassungen für das h4w-Forum. Regeln: Dev-Kit
`standards/conventions.md` §12 – Neuerungen zuerst auf `main`, dann `main` → `h4w` mergen,
nie umgekehrt. Deploy ins h4w-Test-/Live-Forum von diesem Branch.

## Anpassungen

| Bereich | Dateien | Zweck |
|---|---|---|
| CSS-Variablen des h4w-Themes | `inc/plugins/logbooks/assets/logbook.css` | Farben/Größen als `var(--h4w-…, Fallback)` (24 Stellen), damit das h4w-Theme sie zentral steuert. Auf `main` stehen die Fallback-Werte direkt. |
| UserCP-Menü in der RPG-Kategorie | `inc/plugins/logbook.php` | Der UserCP-Menüpunkt „Logbook“ steht in der Kategorie **RPG** (Template `usercp_nav_rpg_chara`, Gruppen-Templates). `logbook_add_usercp_nav_link()` hängt ihn nicht mehr in `usercp_nav_misc` („Sonstiges“), sondern entfernt nur Zeilen älterer Versionen. Link: `logbook.php`, Label `{$lang->logbook_usercp_nav_link}`. |
