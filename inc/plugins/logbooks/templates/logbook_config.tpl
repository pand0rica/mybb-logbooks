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
                    {$savedNotice}
                    <form action="logbook.php?action=save_config" method="post">
                        <input type="hidden" name="my_post_key" value="{$post_key}" />
                        <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                            <thead>
                            <tr>
                                <th class="thead" colspan="2"><strong>{$lang->logbook_page_config}</strong></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="trow1">
                                <td class="lb-form-label" width="30%">{$lang->logbook_form_template}</td>
                                <td>
                                    <select name="template_id">
                                        {$templateIdOptions}
                                    </select>
                                </td>
                            </tr>
                            <tr class="trow2">
                                <td class="lb-form-label" valign="top">{$lang->logbook_form_description}</td>
                                <td><textarea rows="6" cols="55" name="description">{$description}</textarea></td>
                            </tr>
                            <tr class="trow1">
                                <td class="lb-form-label">{$lang->logbook_form_reminder_days}</td>
                                <td>
                                    <input type="number" name="reminder_days" min="0" max="999" value="{$reminder_days}" class="lb-input-narrow" />
                                    <span class="smalltext lb-inline-hint">{$reminder_days_hint}</span>
                                </td>
                            </tr>
                            <tr class="trow2">
                                <td colspan="2" class="lb-align-right">
                                    <input class="button" type="submit" value="{$lang->logbook_btn_save}" />
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
