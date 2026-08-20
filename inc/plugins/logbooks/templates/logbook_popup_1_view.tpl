<html xml:lang="de" lang="de" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <title>{$mybb->settings['bbname']} - Logbook</title>
    {$headerinclude}
</head>
<body class="logbook-popup-body">
    <div id="container" class="lb-popup-wrap">

        <header class="lb-popup-header">
            <svg class="lb-popup-book-icon" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M3 3.5h6a3 3 0 0 1 3 3v10a2.5 2.5 0 0 0-2.5-2.5H3V3.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                <path d="M17 3.5h-6a3 3 0 0 0-3 3v10a2.5 2.5 0 0 1 2.5-2.5H17V3.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            </svg>
            <div class="lb-popup-title">{$logbook_popup_header}</div>
        </header>

        <div class="lb-popup-intro">{$description}</div>

        <div class="lb-scenes-list">
            {$viewbit}
        </div>

    </div>
    <script>
        document.querySelectorAll('.lb-card').forEach(function(card, i) {
            card.style.animationDelay = (i * 0.07) + 's';
        });
    </script>
</body>
</html>