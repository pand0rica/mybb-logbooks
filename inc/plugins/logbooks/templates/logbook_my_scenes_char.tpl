<table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder lb-char-section">
    <thead>
    <tr>
        <th class="thead" colspan="3">
            <strong>
                <a href="logbook.php?action=usercp_view&amp;userId={$char_uid}" class="lb-char-link">{$char_username}</a>
            </strong>
            <span class="lb-char-scene-count">{$char_open_label}</span>
        </th>
    </tr>
    <tr class="tcat">
        <td width="14%">{$lang->logbook_col_next_up}</td>
        <td>{$lang->logbook_col_scene}</td>
        <td align="center" width="17%">{$lang->logbook_col_lastpost}</td>
    </tr>
    </thead>
    <tbody>
    {$char_rows}
    </tbody>
</table>
