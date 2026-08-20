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
                        <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder lb-char-section">
                            <thead>
                            <tr>
                                <th class="thead" colspan="3"><strong>{$lang->logbook_page_my_scenes}</strong></th>
                            </tr>
                            <tr class="tcat">
                                <td width="12%">{$lang->logbook_col_status}</td>
                                <td>{$lang->logbook_col_scene}</td>
                                <td align="center" width="15%">{$lang->logbook_col_action}</td>
                            </tr>
                            </thead>
                            <tbody>
                            {$usercp_viewbit}
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
