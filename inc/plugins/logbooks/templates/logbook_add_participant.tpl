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
                        <form action="logbook.php?action=do_add_participant" method="post">
                            <input type="hidden" name="my_post_key" value="{$post_key}" />
                            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                                <thead>
                                <tr>
                                    <th class="thead" colspan="2"><strong>{$lang->logbook_page_add_self}</strong></th>
                                </tr>
                                </thead>
                                <tbody>
                                {$error}
                                <tr class="trow1">
                                    <td class="lb-form-label" width="25%">{$lang->logbook_form_scene_id}</td>
                                    <td><input type="number" name="sceneId" required></td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label" valign="top">{$lang->logbook_form_description}</td>
                                    <td>
                                        <textarea name="text" cols="50" rows="5"></textarea>
                                        <input type="hidden" name="accepted" value="1">
                                    </td>
                                </tr>
                                <tr class="trow1">
                                    <td colspan="2" class="lb-align-right">
                                        <input class="button" type="submit" value="{$lang->logbook_btn_add}">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </form>
                    </div><!-- /.logbook-wrapper -->

                    <div class="float_right">{$multipage}</div>
                    <br class="clear" />
                </td>
            </tr>
        </table>
        {$footer}
    </body>
</html>
