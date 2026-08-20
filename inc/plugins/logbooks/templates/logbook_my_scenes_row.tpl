<tr class="trow1 lb-my-scenes-row">
    <td class="smalltext lb-my-scenes-cell lb-next-up-cell">
        <span class="lb-mobile-label">{$lang->logbook_col_next_up}:</span>
        {$next_up}
    </td>
    <td>
        <span class="lb-scene-subject">{$title}</span> {$scene_type_badge}
        <div class="logbook-thread-meta lb-scene-meta">
            <span class="logbook-thread-meta-item">
                <svg class="lb-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><rect x="1.5" y="3" width="13" height="11" rx="1.5" stroke="currentColor" stroke-width="1.3"/><path d="M1.5 7h13" stroke="currentColor" stroke-width="1.3"/><path d="M5 1.5V4M11 1.5V4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                {$tag}. {$monat} {$jahr}
            </span>
            <span class="logbook-thread-meta-item">
                <svg class="lb-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M8 1.5C5.52 1.5 3.5 3.52 3.5 6c0 3.75 4.5 8.5 4.5 8.5S12.5 9.75 12.5 6C12.5 3.52 10.48 1.5 8 1.5zM8 7.75A1.75 1.75 0 1 1 8 4.25 1.75 1.75 0 0 1 8 7.75z" fill="currentColor"/></svg>
                {$ort}
            </span>
            <span class="logbook-thread-meta-item">
                <svg class="lb-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3"/><path d="M8 4.5V8l2.5 2" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {$zeit}
            </span>
        </div>
        <div class="logbook-thread-meta lb-scene-meta">
            <span class="logbook-thread-meta-item">
                <svg class="lb-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><circle cx="5.5" cy="5" r="2.5" stroke="currentColor" stroke-width="1.3"/><path d="M1 13.5c0-2.49 2.01-4.5 4.5-4.5S10 11.01 10 13.5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/><circle cx="11.5" cy="5" r="2" stroke="currentColor" stroke-width="1.3"/><path d="M13.5 9.5c1.38.5 2.5 1.8 2.5 4" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
                {$teilnehmer}
            </span>
        </div>
    </td>
    <td align="center" class="smalltext lb-my-scenes-cell lb-lastpost-cell">
        <span class="lb-mobile-label">{$lang->logbook_col_lastpost}:</span>
        {$lastpost_info}
    </td>
</tr>
