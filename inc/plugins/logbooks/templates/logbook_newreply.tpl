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
                    </span>
                    <br><small class="smalltext lb-form-hint">{$lang->logbook_form_participants_hint}</small>
                    <script type="text/javascript">
                    (function () {
                        var field = document.getElementById('logbook-participants');
                        if (!field || !window.jQuery) { return; }
                        var placeholder = field.getAttribute('placeholder') || '';

                        // Preferred: reuse the forum's own Select2 widget – the exact one the
                        // PM recipient field uses – so search behaviour and chips are identical.
                        // get_users already returns Select2's {id,text} shape; we key entries by
                        // the username so the field's submitted value is a comma-joined name list.
                        function initSelect2() {
                            $(field).select2({
                                multiple: true,
                                minimumInputLength: 2,
                                width: '300px',
                                placeholder: placeholder,
                                ajax: {
                                    url: 'xmlhttp.php?action=get_users',
                                    dataType: 'json',
                                    quietMillis: 250,
                                    data: function (term) { return { query: term }; },
                                    results: function (data) {
                                        return { results: $.map(data || [], function (u) {
                                            var name = (u && (u.text || u.id)) || u;
                                            return { id: name, text: name };
                                        }) };
                                    }
                                }
                            });
                        }

                        // Fallback: self-contained chip input with the same data source, used
                        // only if Select2 cannot be loaded. get_users may answer with JSON
                        // [{id,text}] or legacy HTML (<span class="username">) – handle both.
                        function initFallback() {
                            var wrapper = field.parentNode; // .lb-ac-wrap (position: relative)
                            var wrap = document.createElement('div');
                            wrap.className = 'lb-tags';
                            var input = document.createElement('input');
                            input.type = 'text';
                            input.className = 'lb-tags-input';
                            input.autocomplete = 'off';
                            input.setAttribute('placeholder', placeholder);
                            var box = document.createElement('span');
                            box.className = 'lb-ac-list lb-hidden';

                            field.type = 'hidden'; // field now only stores the comma-joined value
                            field.removeAttribute('placeholder');
                            wrapper.insertBefore(wrap, field);
                            wrap.appendChild(input);
                            wrapper.appendChild(box);

                            var chips = [], timer = null, active = -1;

                            function syncValue() { field.value = chips.join(', '); }
                            function renderChips() {
                                $(wrap).find('.lb-tag').remove();
                                for (var idx = chips.length - 1; idx >= 0; idx--) {
                                    (function (i) {
                                        var chip = document.createElement('span');
                                        chip.className = 'lb-tag';
                                        var label = document.createElement('span');
                                        label.textContent = chips[i];
                                        var x = document.createElement('span');
                                        x.className = 'lb-tag-x';
                                        x.textContent = '×';
                                        x.onclick = function () { removeChip(i); input.focus(); };
                                        chip.appendChild(label);
                                        chip.appendChild(x);
                                        wrap.insertBefore(chip, wrap.firstChild);
                                    })(idx);
                                }
                                input.setAttribute('placeholder', chips.length ? '' : placeholder);
                                syncValue();
                            }
                            function addChip(name) {
                                name = $.trim(name);
                                if (!name) { return; }
                                for (var i = 0; i < chips.length; i++) {
                                    if (chips[i].toLowerCase() === name.toLowerCase()) { return; }
                                }
                                chips.push(name);
                                renderChips();
                            }
                            function removeChip(i) { chips.splice(i, 1); renderChips(); }
                            function hide() { box.innerHTML = ''; box.className = 'lb-ac-list lb-hidden'; active = -1; }
                            function choose(name) { input.value = ''; hide(); addChip(name); input.focus(); }
                            function highlight(items) {
                                for (var i = 0; i < items.length; i++) {
                                    items[i].className = (i === active) ? 'lb-ac-item lb-ac-active' : 'lb-ac-item';
                                }
                            }
                            function render(list) {
                                box.innerHTML = '';
                                active = -1;
                                if (!list.length) { box.className = 'lb-ac-list lb-hidden'; return; }
                                list.forEach(function (n) {
                                    var item = document.createElement('span');
                                    item.className = 'lb-ac-item';
                                    item.textContent = n;
                                    item.onmousedown = function (e) { e.preventDefault(); choose(n); };
                                    box.appendChild(item);
                                });
                                box.className = 'lb-ac-list';
                            }
                            function search(q) {
                                $.get('xmlhttp.php?action=get_users&query=' + encodeURIComponent(q), function (data) {
                                    var list = [];
                                    if (data && typeof data === 'object') {
                                        $.each(data, function (_, u) { if (u && u.text) { list.push(u.text); } });
                                    } else {
                                        var parsed = null;
                                        try { parsed = JSON.parse(data); } catch (e) {}
                                        if (parsed && typeof parsed.length === 'number') {
                                            $.each(parsed, function (_, u) { list.push((u && (u.text || u.id)) || u); });
                                        } else {
                                            $('<div>').html(data).find('span.username').each(function () {
                                                list.push($(this).text());
                                            });
                                        }
                                    }
                                    list = $.grep(list, function (n) {
                                        for (var i = 0; i < chips.length; i++) {
                                            if (chips[i].toLowerCase() === n.toLowerCase()) { return false; }
                                        }
                                        return true;
                                    });
                                    render(list);
                                });
                            }

                            wrap.addEventListener('click', function (e) { if (e.target === wrap) { input.focus(); } });
                            input.addEventListener('focus', function () { wrap.className = 'lb-tags lb-focus'; });
                            input.addEventListener('blur', function () { wrap.className = 'lb-tags'; setTimeout(hide, 150); });
                            input.addEventListener('input', function () {
                                var q = $.trim(input.value);
                                if (q.length < 2) { hide(); return; }
                                clearTimeout(timer);
                                timer = setTimeout(function () { search(q); }, 250);
                            });
                            input.addEventListener('keydown', function (e) {
                                var items = box.getElementsByClassName('lb-ac-item');
                                if (e.key === 'Backspace' && input.value === '' && chips.length) {
                                    removeChip(chips.length - 1);
                                    return;
                                }
                                if (!items.length) { return; }
                                if (e.key === 'ArrowDown') {
                                    e.preventDefault(); active = (active + 1) % items.length; highlight(items);
                                } else if (e.key === 'ArrowUp') {
                                    e.preventDefault(); active = (active - 1 + items.length) % items.length; highlight(items);
                                } else if (e.key === 'Enter') {
                                    if (active >= 0 && items[active]) { e.preventDefault(); choose(items[active].textContent); }
                                } else if (e.key === 'Escape') {
                                    hide();
                                }
                            });
                            if (field.form) {
                                field.form.addEventListener('submit', function () {
                                    if ($.trim(input.value)) { addChip(input.value); input.value = ''; }
                                });
                            }
                        }

                        if ($.fn && $.fn.select2) { initSelect2(); return; }

                        // Select2 isn't loaded on the new-thread page by default – pull in the
                        // same copy the PM page uses, then fall back to the chip widget on failure.
                        var settled = false;
                        function tryInit() {
                            if ($.fn && $.fn.select2) { if (!settled) { settled = true; initSelect2(); } return true; }
                            return false;
                        }
                        function useFallback() { if (!settled) { settled = true; initFallback(); } }
                        try {
                            var css = document.createElement('link');
                            css.rel = 'stylesheet';
                            css.href = 'jscripts/select2/select2.css';
                            document.head.appendChild(css);
                            var s = document.createElement('script');
                            s.src = 'jscripts/select2/select2.min.js';
                            s.onload  = function () { if (!tryInit()) { useFallback(); } };
                            s.onerror = useFallback;
                            document.head.appendChild(s);
                            setTimeout(function () { if (!settled && !tryInit()) { useFallback(); } }, 4000);
                        } catch (e) { useFallback(); }
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
