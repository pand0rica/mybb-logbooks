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
                        <form action="logbook.php?action=scene_save" method="post" id="lb-add-scene-form">
                            <input type="hidden" name="my_post_key" value="{$post_key}" />
                            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder">
                                <thead>
                                <tr>
                                    <th class="thead" colspan="2"><strong>{$lang->logbook_page_add_scene}</strong></th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr class="trow1">
                                    <td class="lb-form-label" width="25%">{$lang->logbook_form_scene_type}</td>
                                    <td>
                                        <select name="scene_type" id="lb-scene-type" onchange="lbUpdateSceneType(this.value)">
                                            <option value="0"{$scene_type_sel[0]}>{$lang->logbook_scene_type_normal}</option>
                                            <option value="1"{$scene_type_sel[1]}>{$lang->logbook_scene_type_au}</option>
                                            <option value="2"{$scene_type_sel[2]}>{$lang->logbook_scene_type_unplayed}</option>
                                            <option value="3"{$scene_type_sel[3]}>{$lang->logbook_scene_type_planned}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="trow2" id="lb-row-thread">
                                    <td class="lb-form-label">{$lang->logbook_form_thread_id}</td>
                                    <td><input type="text" name="threadId" id="lb-thread-id"></td>
                                </tr>
                                <tr class="trow2 lb-hidden" id="lb-row-title">
                                    <td class="lb-form-label">{$lang->logbook_form_scene_title}</td>
                                    <td><input type="text" name="scene_title" id="lb-scene-title" class="lb-input-title"></td>
                                </tr>
                                <tr class="trow1 lb-hidden" id="lb-row-visibility">
                                    <td class="lb-form-label">{$lang->logbook_form_visibility}</td>
                                    <td>
                                        <label><input type="radio" name="visibility" value="1" checked> {$lang->logbook_visibility_public}</label>
                                        &nbsp;&nbsp;
                                        <label><input type="radio" name="visibility" value="0"> {$lang->logbook_visibility_private}</label>
                                    </td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_location}</td>
                                    <td><input name="ort" type="text" /></td>
                                </tr>
                                <tr class="trow1">
                                    <td class="lb-form-label">{$lang->logbook_form_year}</td>
                                    <td><input type="number" name="jahr" min="2000" max="2500" /></td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_month}</td>
                                    <td><input type="number" name="monat" min="1" max="12" /></td>
                                </tr>
                                <tr class="trow1">
                                    <td class="lb-form-label">{$lang->logbook_form_day}</td>
                                    <td><input type="number" name="tag" min="1" max="31" /></td>
                                </tr>
                                <tr class="trow2">
                                    <td class="lb-form-label">{$lang->logbook_form_time}</td>
                                    <td>
                                        <select name="zeit">
                                            <option value="1">{$lang->logbook_time_morning}</option>
                                            <option value="2">{$lang->logbook_time_noon}</option>
                                            <option value="3">{$lang->logbook_time_afternoon}</option>
                                            <option value="4">{$lang->logbook_time_evening}</option>
                                            <option value="5">{$lang->logbook_time_night}</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr class="trow2" id="lb-row-free-order">
                                    <td class="lb-form-label">{$lang->logbook_form_free_order}</td>
                                    <td>
                                        <label><input type="checkbox" name="free_order" value="1"> {$lang->logbook_form_free_order_hint}</label>
                                    </td>
                                </tr>
                                <tr class="trow1">
                                    <td class="lb-form-label">{$lang->logbook_form_participants}</td>
                                    <td><input class="button" type="button" onclick="addUser()" value="{$lang->logbook_btn_add_participant}"></td>
                                </tr>
                                <tr class="trow1">
                                    <td colspan="2">
                                        <div id="users">
                                            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder lb-table-flush">
                                                <tr class="tcat">
                                                    <td colspan="2">{$lang->logbook_form_participant_first}</td>
                                                </tr>
                                                <tr class="trow1">
                                                    <td class="lb-form-label" width="20%">{$lang->logbook_form_name}</td>
                                                    <td>
                                                        <select class="user-select-0 lb-user-select" name="user[0][userId]">
                                                            {$availableUsers}
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr class="trow2">
                                                    <td class="lb-form-label" valign="top">{$lang->logbook_form_description}</td>
                                                    <td>
                                                        <textarea name="user[0][text]" cols="47" rows="5"></textarea>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="trow2">
                                    <td colspan="2" class="lb-align-right">
                                        <input class="button" type="submit" value="{$lang->logbook_btn_save}">
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

        <!-- Hidden template for dynamically added participant rows -->
        <div class="user-row lb-hidden">
            <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder lb-table-flush">
                <tr class="tcat">
                    <td colspan="2">{$lang->logbook_form_participant}</td>
                </tr>
                <tr class="trow1">
                    <td class="lb-form-label" width="20%">{$lang->logbook_form_name}</td>
                    <td>
                        <select class="user-select-#ID# lb-user-select" name="user[#ID#][userId]">
                            {$availableUsers}
                        </select>
                    </td>
                </tr>
                <tr class="trow2">
                    <td colspan="2" class="lb-align-right">
                        <input class="button" type="button" onclick="removeUser(this)" value="{$lang->logbook_btn_remove}">
                    </td>
                </tr>
            </table>
        </div>

        <script type="text/javascript">
            var i = 1;
            $(document).ready(function() {
                $(".user-select-0").val({$userId});
                lbUpdateSceneType($("#lb-scene-type").val());
            });

            function lbUpdateSceneType(type) {
                type = parseInt(type, 10);
                var needsThread = (type === 0 || type === 1);
                $("#lb-row-thread").toggle(needsThread);
                $("#lb-thread-id").prop("required", needsThread);
                $("#lb-row-title").toggle(!needsThread);
                $("#lb-row-visibility").toggle(!needsThread);
                $("#lb-row-free-order").toggle(needsThread);
            }

            function addUser() {
                var elem = $(".user-row").clone().html();
                elem = elem.replace(/(#ID#)/g, i);
                $("#users").append(elem);
                i++;
            }

            function removeUser(element) {
                $(element).closest("table").remove();
            }
        </script>
    </body>
</html>
