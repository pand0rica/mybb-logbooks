<div class="lb-filter-section">
    <form method="get" action="logbook.php" class="lb-filter-form">
        <input type="hidden" name="action" value="my_scenes" />
        {$filter_userid_input}
        <div class="lb-filter-bar">
            <div class="lb-filter-row">
                <span class="lb-filter-label">{$lang->logbook_filter_label}</span>

                <label class="lb-filter-item lb-filter-checkbox">
                    <input type="checkbox" name="filter_open" value="1" {$filter_open_checked} />
                    {$lang->logbook_filter_only_open}
                </label>

                <select name="filter_category" class="lb-filter-select">
                    {$filter_cat_options}
                </select>

                <label class="lb-filter-days-wrap">
                    <input type="number" name="filter_days" value="{$filter_days_value}" min="1" class="lb-filter-input-days" placeholder="–" />
                    {$lang->logbook_filter_days_label}
                </label>

                <select name="filter_month" class="lb-filter-select">
                    {$filter_month_options_html}
                </select>
            </div>
            <div class="lb-filter-row">
                <span class="lb-filter-label">{$lang->logbook_sort_label}</span>

                <select name="sort" class="lb-filter-select">
                    {$sort_options}
                </select>

                <label class="lb-filter-item lb-filter-checkbox">
                    <input type="checkbox" name="filter_no_grouping" value="1" {$filter_no_grouping_checked} />
                    {$lang->logbook_filter_no_grouping}
                </label>
            </div>
            <div class="lb-filter-row lb-filter-actions">
                <input type="submit" class="button lb-filter-btn" value="{$lang->logbook_filter_apply}" />
                <a href="logbook.php?action=my_scenes{$filter_reset_suffix}" class="lb-filter-reset">{$lang->logbook_filter_reset}</a>
            </div>
        </div>
    </form>
</div>
