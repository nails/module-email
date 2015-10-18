<?php

return array(
    'services' => array(
        'Emailer' => function() {
            if (class_exists('\App\Email\Library\Emailer')) {
                return new \App\Email\Library\Emailer();
            } else {
                return new \Nails\Email\Library\Emailer();
            }
        }
    )
);
