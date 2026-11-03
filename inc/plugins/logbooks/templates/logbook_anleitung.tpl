<html>
<head>
    <title>Logbook – {$mybb->settings['bbname']}</title>
    {$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
    <tr>
        {$usercpnav}
        <td valign="top">
            <div class="float_right">{$multipage}</div>
            <div class="clear"></div>

            <div class="logbook-wrapper">
                <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                    <thead>
                    <tr>
                        <th class="thead" colspan="3"><strong>{$lang->logbook_page_guide}</strong></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr class="trow1">
                        <td class="lb-guide-body">

                            Das Logbook hilft dir, den Überblick über alle deine Inplay-Szenen zu behalten – auf einen Blick siehst du, was gerade läuft, wer dran ist und was schon abgeschlossen wurde.

                            <!-- ═══ 1. Automatische Erfassung ═══ -->
                            <h3>1 &nbsp;Automatische Erfassung neuer Szenen</h3>
                            Wenn du in einem Inplay-Forum einen neuen Thread erstellst, erscheint unterhalb des normalen Erstellungsformulars ein zusätzlicher Bereich – das Szenen-Formular. Fülle dort aus:
                            <ul class="lb-guide-list lb-guide-list-tight">
                                <li><strong>Ort</strong> – wo findet die Szene statt?</li>
                                <li><strong>Jahr, Monat, Tag</strong> – das Inplay-Datum der Szene</li>
                                <li><strong>Tageszeit</strong> – vormittags / mittags / nachmittags / abends / nachts</li>
                                <li><strong>Freie Reihenfolge</strong> – aktivieren, wenn keine feste Posting-Reihenfolge gilt</li>
                                <li><strong>Teilnehmende</strong> – du bist automatisch eingetragen; weitere Charaktere kannst du über „+ Teilnehmende hinzufügen" ergänzen</li>
                            </ul>
                            Die Szene wird nach dem Absenden sofort in allen Logbooks der eingetragenen Charaktere gespeichert.

                            <!-- ═══ 2. Szenentypen ═══ -->
                            <h3>2 &nbsp;Szenentypen</h3>
                            Jede Szene hat einen Typ – er bestimmt, wie die Szene im Logbook angezeigt wird:
                            <ul class="lb-guide-list">
                                <li><strong>Normal</strong> – eine reguläre Inplay-Szene, verknüpft mit einem Forum-Thread</li>
                                <li><strong>AU (Alternate Universe)</strong> – eine AU-Szene, ebenfalls mit Thread verknüpft</li>
                                <li><strong>Unausgespielt</strong> – eine Szene, die nur im Logbook vermerkt ist (kein Thread); du kannst selbst einen Titel vergeben und wählen, ob sie öffentlich oder privat sichtbar ist</li>
                                <li><strong>Geplant</strong> – eine geplante, aber noch nicht gespielte Szene (kein Thread); ebenfalls mit eigenem Titel und Sichtbarkeit</li>
                            </ul>

                            <!-- ═══ 3. Logbook ansehen & bearbeiten ═══ -->
                            <h3>3 &nbsp;Logbook ansehen &amp; bearbeiten</h3>
                            Unter <strong>„Logbook ansehen &amp; bearbeiten"</strong> siehst du alle deine Szenen nach Charakter sortiert. Dort kannst du:
                            <ul class="lb-guide-list lb-guide-list-tight">
                                <li>Eine Szene als <strong>beendet</strong> markieren (✓) oder wieder auf <strong>aktiv</strong> setzen (↺)</li>
                                <li>Eine Szene <strong>bearbeiten</strong> – Ort, Datum, Zeit, Teilnehmende, Beschreibung und Posting-Erinnerung anpassen</li>
                                <li>Eine Szene <strong>löschen</strong> (✕)</li>
                            </ul>
                            Im Bearbeitungsformular kannst du auch eine <strong>persönliche Beschreibung</strong> für deinen Eintrag hinterlegen – dieser Text ist nur in deinem Logbook sichtbar.

                            <!-- ═══ 4. Aktuelle Szenen (Übersicht) ═══ -->
                            <h3>4 &nbsp;Aktuelle Szenen – alle Charaktere</h3>
                            Die Ansicht <strong>„Aktuelle Szenen"</strong> zeigt dir alle laufenden Szenen über alle deine Charaktere auf einen Blick. Du erkennst dort:
                            <ul class="lb-guide-list lb-guide-list-tight">
                                <li>Wer als Nächstes dran ist (<em>„Du bist dran"</em>-Markierung)</li>
                                <li>Das Inplay-Datum und den Ort der Szene</li>
                                <li>Alle Teilnehmenden</li>
                                <li>Ob freie Reihenfolge gilt</li>
                            </ul>
                            Mit der <strong>Filterleiste</strong> kannst du die Liste eingrenzen:
                            <ul class="lb-guide-list">
                                <li><strong>„Szenen, in denen ich dran bin"</strong> – zeigt nur Szenen, in denen du als Nächstes posten musst</li>
                                <li><strong>Kategorie</strong> – filtert nach dem Inplay-Forum der Szene</li>
                                <li><strong>Tage ohne Antwort</strong> – zeigt nur Szenen, in denen seit X Tagen niemand gepostet hat</li>
                                <li><strong>Inplay-Monat</strong> – filtert nach dem Inplay-Monat der Szene</li>
                                <li><strong>Sortierung</strong> – nach letztem Beitrag oder nach Inplay-Datum, jeweils auf- oder absteigend</li>
                            </ul>

                            <!-- ═══ 5. Posting-Erinnerungen ═══ -->
                            <h3>5 &nbsp;Posting-Erinnerungen</h3>
                            Das Logbook erinnert dich auf der <strong>Startseite</strong>, wenn du in einer Szene längere Zeit nicht gepostet hast. Wie viele Tage vergehen müssen, bis eine Erinnerung erscheint, kannst du einstellen:
                            <ul class="lb-guide-list">
                                <li><strong>Global (für alle deine Szenen)</strong> – unter „Logbook konfigurieren" → Feld „Posting-Erinnerung"</li>
                                <li><strong>Pro Szene</strong> – im Bearbeitungsformular der jeweiligen Szene; ein Wert von 0 übernimmt die globale Einstellung</li>
                            </ul>

                            <!-- ═══ 6. Logbook konfigurieren ═══ -->
                            <h3>6 &nbsp;Logbook konfigurieren</h3>
                            Unter <strong>„Logbook konfigurieren"</strong> kannst du einmalig festlegen:
                            <ul class="lb-guide-list">
                                <li><strong>Template</strong> – das Design deines öffentlichen Logbook-Popups</li>
                                <li><strong>Beschreibung</strong> – ein kurzer Text, der im Popup oben angezeigt wird (z.&nbsp;B. Infos zu deinem Charakter)</li>
                                <li><strong>Posting-Erinnerung</strong> – Anzahl Tage, nach denen eine Erinnerung erscheint (0 = Forumsstandard)</li>
                            </ul>

                            <!-- ═══ 7. Logbook-Popup ═══ -->
                            <h3>7 &nbsp;Logbook-Popup öffnen</h3>
                            Über <strong>„Logbook-Popup öffnen"</strong> siehst du dein Logbook so, wie es andere Mitglieder sehen. Den Link kannst du in dein Profil oder deine Signatur eintragen, damit andere dein Logbook aufrufen können. Szenen vom Typ „Privat" sind dort nur für Teilnehmende sichtbar.

                            <!-- ═══ 8. Einträge manuell hinzufügen ═══ -->
                            <h3>8 &nbsp;Einträge manuell hinzufügen</h3>
                            Unter <strong>„Einträge hinzufügen"</strong> kannst du Szenen nachtragen, die noch nicht im Logbook erfasst sind. Gib dazu die <strong>Thread-ID</strong> ein – die findest du in der URL des Threads (z.&nbsp;B. <code>…&amp;tid=<strong>123</strong></code>). Du kannst hier auch Szenen ohne Thread (Typ „Unausgespielt" oder „Geplant") eintragen.<br />
                            Du wirst beim manuellen Eintragen automatisch als erste teilnehmende Person gesetzt. Weitere Charaktere lassen sich über „+ Teilnehmende hinzufügen" ergänzen.

                            <!-- ═══ 9. Sich selbst hinzufügen ═══ -->
                            <h3>9 &nbsp;Sich selbst zu einer Szene hinzufügen</h3>
                            Wenn du an einer Szene teilnimmst, die eine andere Person erstellt hat und die noch nicht in deinem Logbook steht, kannst du dich unter <strong>„Sich selbst zu einer Szene hinzufügen"</strong> eintragen. Gib die Thread-ID der Szene ein und optional eine persönliche Beschreibung. Die Szene erscheint danach in deinem Logbook.

                        </td>
                    </tr>
                    </tbody>
                </table>
            </div><!-- /.logbook-wrapper -->

            <br class="clear" />
        </td>
    </tr>
</table>
{$footer}
</body>
</html>