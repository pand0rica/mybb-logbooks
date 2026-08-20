<html>
<head>
    <title>{$page_title} – {$mybb->settings['bbname']}</title>
    {$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
    <tr>
        <td valign="top">
            <div class="logbook-wrapper">

                <table border="0" cellspacing="{$theme['borderwidth']}" cellpadding="{$theme['tablespace']}" class="tborder lb-my-scenes-title-table">
                    <thead>
                    <tr>
                        <th class="thead">
                            <strong>{$page_title}</strong>
                            <span class="lb-my-scenes-back">
                                <a href="logbook.php">← {$lang->logbook_breadcrumb_cp}</a>
                            </span>
                        </th>
                    </tr>
                    </thead>
                </table>

                <p class="lb-my-scenes-total">{$total_open_label}</p>

                {$filter_section}

                {$characters}

            </div>
            <br class="clear" />
        </td>
    </tr>
</table>
{$footer}
</body>
</html>
