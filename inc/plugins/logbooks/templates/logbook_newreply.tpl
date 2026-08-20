<tr class="trow1">
    <td valign="top"><strong>{$lang->logbook_form_add_to_logbook}</strong></td>
    <td>
        <table border="0" cellspacing="0" cellpadding="4" class="lb-form-table">
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_participants}</td>
                <td>
                    <span class="lb-ac-wrap">
                        <input type="text" name="participants_names" id="logbook-participants" autocomplete="off"
                               class="lb-user-select" placeholder="{$lang->logbook_form_participants_placeholder}" />
                        <span id="logbook-participants-ac" class="lb-ac-list lb-hidden"></span>
                    </span>
                    <br><small class="smalltext lb-form-hint">{$lang->logbook_form_participants_hint}</small>
                    <script type="text/javascript">
                    (function () {
                        var input = document.getElementById('logbook-participants');
                        var box   = document.getElementById('logbook-participants-ac');
                        if (!input || !box) { return; }
                        var timer = null;

                        function currentToken() {
                            var parts = input.value.split(',');
                            return parts[parts.length - 1].replace(/^\s+/, '');
                        }
                        function hide() { box.innerHTML = ''; box.className = 'lb-ac-list lb-hidden'; }
                        function applyName(name) {
                            var parts = input.value.split(',');
                            parts[parts.length - 1] = ' ' + name;
                            input.value = parts.join(',').replace(/^\s+/, '') + ', ';
                            hide();
                            input.focus();
                        }
                        input.addEventListener('input', function () {
                            var q = currentToken();
                            if (q.length < 3) { hide(); return; }
                            clearTimeout(timer);
                            timer = setTimeout(function () {
                                $.get('xmlhttp.php?action=get_users&query=' + encodeURIComponent(q), function (data) {
                                    // MyBB's get_users returns one of two formats depending on
                                    // version/modifications: JSON [{id,text}] (auto-parsed to an
                                    // array by jQuery, or a JSON string) or legacy HTML with
                                    // <span class="username">. Handle all of them.
                                    var list = [];
                                    if (data && typeof data === 'object') {
                                        $.each(data, function (_, u) {
                                            if (u && u.text) { list.push(u.text); }
                                        });
                                    } else {
                                        var parsed = null;
                                        try { parsed = JSON.parse(data); } catch (e) {}
                                        if (parsed && typeof parsed.length === 'number') {
                                            $.each(parsed, function (_, u) {
                                                list.push((u && (u.text || u.id)) || u);
                                            });
                                        } else {
                                            $('<div>').html(data).find('span.username').each(function () {
                                                list.push($(this).text());
                                            });
                                        }
                                    }
                                    box.innerHTML = '';
                                    if (!list.length) { box.className = 'lb-ac-list lb-hidden'; return; }
                                    list.forEach(function (n) {
                                        var item = document.createElement('span');
                                        item.className = 'lb-ac-item';
                                        item.textContent = n;
                                        item.onmousedown = function (e) { e.preventDefault(); applyName(n); };
                                        box.appendChild(item);
                                    });
                                    box.className = 'lb-ac-list';
                                });
                            }, 250);
                        });
                        input.addEventListener('blur', function () {
                            setTimeout(hide, 150);
                        });
                    })();
                    </script>
                </td>
            </tr>
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_location}</td>
                <td><input name="ort" type="text" required /></td>
            </tr>
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_year}</td>
                <td><input type="number" name="jahr" min="2000" max="2500" required /></td>
            </tr>
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_month}</td>
                <td><input type="number" name="monat" min="1" max="12" required /></td>
            </tr>
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_day}</td>
                <td><input type="number" name="tag" min="1" max="31" required /></td>
            </tr>
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_time}</td>
                <td>
                    <select name="zeit" required>
                        <option value="1">{$lang->logbook_time_morning}</option>
                        <option value="2">{$lang->logbook_time_noon}</option>
                        <option value="3">{$lang->logbook_time_afternoon}</option>
                        <option value="4">{$lang->logbook_time_evening}</option>
                        <option value="5">{$lang->logbook_time_night}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="lb-form-label">{$lang->logbook_form_free_order}</td>
                <td>
                    <label><input type="checkbox" name="free_order" value="1"> {$lang->logbook_form_free_order_hint}</label>
                </td>
            </tr>
        </table>
    </td>
</tr>
