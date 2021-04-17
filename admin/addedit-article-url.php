<?php
/**
 * @var StdClass $to
 */
?>
<form action="addedit-article.php" method="get" data-ajax="false">

    <div data-role="header" data-theme="e">
        <h2>Import Article</h2>
    </div>

    <ul data-role="listview">
        <li data-role="fieldcontain" class="search-row">
            <input type="url" name="url" placeholder="paste url" value="<?= $to->importUrl ?>" autocomplete="off" />
            <button type="submit" data-inline="true">Go</button>
        </li>
    </ul>

</form>