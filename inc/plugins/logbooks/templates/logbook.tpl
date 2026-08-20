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
                <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="0" class="tborder">
                    <thead>
                    <tr>
                        <th class="thead" colspan="2"><strong>{$lang->logbook_page_cp}</strong></th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="trow1 lb-cell-flush">
                            <ul class="lb-nav-list">
                                <li>
                                    <a href="logbook.php?action=anleitung" class="lb-nav-link">{$lang->logbook_nav_guide}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_guide_desc}</div>
                                </li>
                                <li>
                                    <a href="logbook.php?action=config" class="lb-nav-link">{$lang->logbook_nav_configure}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_configure_desc}</div>
                                </li>
                                <li>
                                    <a href="logbook.php?action=scene_add" class="lb-nav-link">{$lang->logbook_nav_add}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_add_desc}</div>
                                </li>
                                <li>
                                    <a href="logbook.php?action=usercp_view" class="lb-nav-link">{$lang->logbook_nav_view}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_view_desc}</div>
                                </li>
                                <li>
                                    <a href="logbook.php?action=add_participant" class="lb-nav-link">{$lang->logbook_nav_add_self}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_add_self_desc}</div>
                                </li>
                                <li>
                                    <a href="logbook.php?action=popup_view&userId={$mybb->user['uid']}" target="_blank" class="lb-nav-link">{$lang->logbook_nav_popup}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_popup_desc}</div>
                                </li>
                                <li>
                                    <a href="logbook.php?action=my_scenes" class="lb-nav-link">{$lang->logbook_nav_my_scenes}</a>
                                    <div class="lb-nav-desc">{$lang->logbook_nav_my_scenes_desc}</div>
                                </li>
                            </ul>

                            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder lb-stats">
                                <thead>
                                <tr><th class="thead" colspan="2">{$lang->logbook_stats}</th></tr>
                                </thead>
                                <tbody>
                                <tr class="trow1">
                                    <td>{$lang->logbook_stats_total}</td>
                                    <td class="lb-align-right">{$sceneCount}</td>
                                </tr>
                                <tr class="trow2">
                                    <td>{$lang->logbook_stats_active}</td>
                                    <td class="lb-align-right">{$sceneActiveCount}</td>
                                </tr>
                                <tr class="trow1">
                                    <td>{$lang->logbook_stats_finished}</td>
                                    <td class="lb-align-right">{$sceneFinishedCount}</td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div><!-- /.logbook-wrapper -->

            <div class="float_right">{$multipage}</div>
            <br class="clear" />
        </td>
    </tr>
</table>
{$footer}
</body>
</html>