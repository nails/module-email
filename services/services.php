<?php

return array(
    'services' => array(
        'Emailer' => function() {

            $oCi = get_instance();
            $oCi->load->library('email/emailer');

            return $oCi->emailer;
        }
    )
);
