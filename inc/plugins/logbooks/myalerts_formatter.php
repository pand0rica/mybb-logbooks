<?php
/**
 * Logbook – MyAlerts Formatter
 *
 * Formats logbook_scene_invite alerts for display in MyAlerts notifications.
 */

if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

class Logbook_MyAlertsFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
{
    public function init(): void
    {
        $this->lang->load('logbook');
    }

    /**
     * @param MybbStuff_MyAlerts_Entity_Alert $alert
     * @param array                           $outputAlert
     * @return string
     */
    public function formatAlert(
        MybbStuff_MyAlerts_Entity_Alert $alert,
        array $outputAlert
    ): string {
        $details = $alert->getExtraDetails();
        $subject = isset($details['subject']) ? htmlspecialchars_uni($details['subject']) : '';

        return $this->lang->sprintf(
            $this->lang->logbook_alert_scene_invite,
            $subject
        );
    }

    /**
     * @param MybbStuff_MyAlerts_Entity_Alert $alert
     * @return string
     */
    public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert): string
    {
        $details = $alert->getExtraDetails();
        return 'showthread.php?tid=' . (int)($details['tid'] ?? 0);
    }
}
