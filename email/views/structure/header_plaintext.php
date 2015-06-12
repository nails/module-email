<?=strtoupper($email_subject)?>

---------------

<?php

if (isset($sent_to->first) && $sent_to->first && empty($email_no_greeting)) {

    echo 'Hi ' . $sent_to->first . ',' . "\n\n";

}
