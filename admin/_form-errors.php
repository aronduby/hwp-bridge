<?php
if(isset($form_errors)){
    print '<div data-role="content" data-theme="e" class="ui-alert">';
    print $form_errors;
    print '</div>';
}