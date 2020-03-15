<?php
if (!$season->current) {
    ?>
    <div data-role="content" data-theme="e" class="ui-shadow ui-alert">
        You are currently editing a season that is <strong>not</strong> the current season. Would you like to
        <a href="season-make-active.php?clear=1" data-ajax="false">edit the active season</a>?
    </div>
    <?php
}