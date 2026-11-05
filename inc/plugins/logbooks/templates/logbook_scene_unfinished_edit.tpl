<form method="post" action="logbook.php" class="lb-inline-form">
    <input type="hidden" name="action" value="scene_change_finished" />
    <input type="hidden" name="scene_id" value="{$scene_id}" />
    <input type="hidden" name="my_post_key" value="{$post_key}" />
    <input type="submit" class="button lb-action" title="{$lang->logbook_tooltip_mark_finish}" value="{$lang->logbook_action_mark_finish}" />
</form>
