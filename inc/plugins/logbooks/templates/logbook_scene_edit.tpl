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
                        <form action="logbook.php?action=scene_edit_save" method="post">
                            <input type="hidden" name="my_post_key" value="{$post_key}" />
                            <input type="hidden" name="sceneId" value="{$id}">

                            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                                <thead>
                                <tr>
                                    <th class="tcat" colspan="2"><strong>{$lang->logbook_page_edit_scene}</strong></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr class="trow1">
                                    <td class="lb-form-label" width="25%">{$lang->logbook_form_scene_type}</td>
                                    <td>
                                        <select name="scene_type" id="lb-scene-type-edit" onchange="lbUpdateSceneType(this.value)">
                                            <option value="0"{$scene_type_sel[0]}>{$lang->logbook_scene_type_normal}</option>
                                            <option value="1"{$scene_type_sel[1]}>{$lang->logbook_scene_type_au}</option>
                                            <option value="2"{$scene_type_sel[2]}>{$lang->logbook_scene_type_unplayed}</option>
                                            <option value="3"{$scene_type_sel[3]}>{$lang->logbook_scene_type_planned}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="trow2" id="lb-row-thread">
                                    <td class="lb-form-label">{$lang->logbook_form_thread_id}</td>
                                    <td><input type="text" name="threadId" id="lb-thread-id" value="{$data['tid']}"></td>
                                </tr>
                                <tr class="trow2" id="lb-row-title">
                                    <td class="lb-form-label">{$lang->logbook_form_scene_title}</td>
                                    <td><input type="text" name="scene_title" id="lb-scene-title" value="{$data['title']}" class="lb-input-title"></td>
                                </tr>
                                <tr class="trow1" id="lb-row-visibility">
                                    <td class="lb-form-label">{$lang->logbook_form_visibility}</td>
                                    <td>
                                        <label><input type="radio" name="visibility" value="1"{$visibility_sel[1]}> {$lang->logbook_visibility_public}</label>
                                        &nbsp;&nbsp;
                                        <label><input type="radio" name="visibility" value="0"{$visibility_sel[0]}> {$lang->logbook_visibility_private}</label>
                                    </td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_location}</td>
                                    <td><input name="ort" type="text" value="{$data['ort']}"/></td>
                                </tr>
                                <tr class="trow1">
                                    <td class="lb-form-label">{$lang->logbook_form_year}</td>
                                    <td><input type="number" name="jahr" min="2000" max="2500" value="{$data['jahr']}" /></td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_month}</td>
                                    <td><input type="number" name="monat" min="1" max="12" value="{$data['monat']}" /></td>
                                </tr>
                                <tr class="trow1">
                                    <td class="lb-form-label">{$lang->logbook_form_day}</td>
                                    <td><input type="number" name="tag" min="1" max="31" value="{$data['tag']}" /></td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_time}</td>
                                    <td>
                                        <select name="zeit">
                                            <option value="0"{$time[0]}>{$lang->logbook_opt_no_time}</option>
                                            <option value="1"{$time[1]}>{$lang->logbook_time_morning}</option>
                                            <option value="2"{$time[2]}>{$lang->logbook_time_noon}</option>
                                            <option value="3"{$time[3]}>{$lang->logbook_time_afternoon}</option>
                                            <option value="4"{$time[4]}>{$lang->logbook_time_evening}</option>
                                            <option value="5"{$time[5]}>{$lang->logbook_time_night}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="trow1">
                                    <td class="lb-form-label">{$lang->logbook_form_active}</td>
                                    <td>
                                        <select name="finished">
                                            <option value="0"{$finished[0]}>{$lang->logbook_opt_yes}</option>
                                            <option value="1"{$finished[1]}>{$lang->logbook_opt_no}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="trow2" id="lb-row-free-order">
                                    <td class="lb-form-label">{$lang->logbook_form_free_order}</td>
                                    <td>
                                        <label><input type="checkbox" name="free_order" value="1"{$free_order_checked}> {$lang->logbook_form_free_order_hint}</label>
                                    </td>
                                </tr>
                                </tbody>
                            </table>

                            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                                <thead>
                                <tr>
                                    <th class="tcat" colspan="2"><strong>{$lang->logbook_page_edit_entry}</strong></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr class="trow1">
                                    <td class="lb-form-label" valign="top">{$lang->logbook_form_description}</td>
                                    <td><textarea name="description" cols="47" rows="5">{$entry['text']}</textarea></td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_reminder_days}</td>
                                    <td>
                                        <input type="number" name="entry_reminder_days" min="0" max="999" value="{$entry_reminder_days}" class="lb-input-narrow" />
                                        <span class="smalltext lb-inline-hint">{$lang->logbook_form_reminder_days_scene_hint}</span>
                                    </td>
                                </tr>
                                <tr class="trow1">
                                    <td colspan="2" class="lb-align-right">
                                        <input class="button" type="submit" value="{$lang->logbook_btn_save}">
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </form>

                        <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                            <thead>
                            <tr>
                                <th class="tcat" colspan="2"><strong>{$lang->logbook_page_participants}</strong></th>
                            </tr>
                            <tr>
                                <td class="tcat" width="75%"><span class="smalltext">{$lang->logbook_col_participants}</span></td>
                                <td class="tcat lb-align-right"><span class="smalltext">{$lang->logbook_col_action}</span></td>
                            </tr>
                            </thead>
                            <tbody>
                                {$participant_rows}
                            </tbody>
                        </table>
                    </div><!-- /.logbook-wrapper -->

                    <div class="float_right">{$multipage}</div>
                    <br class="clear" />
                </td>
            </tr>
        </table>
        {$footer}

        <script type="text/javascript">
            function lbUpdateSceneType(type) {
                type = parseInt(type, 10);
                var needsThread = (type === 0 || type === 1);
                document.getElementById("lb-row-thread").style.display = needsThread ? "" : "none";
                document.getElementById("lb-thread-id").required = needsThread;
                document.getElementById("lb-row-title").style.display = needsThread ? "none" : "";
                document.getElementById("lb-row-visibility").style.display = needsThread ? "none" : "";
                document.getElementById("lb-row-free-order").style.display = needsThread ? "" : "none";
            }
            lbUpdateSceneType(document.getElementById("lb-scene-type-edit").value);
        </script>
    </body>
</html>
