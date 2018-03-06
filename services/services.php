<?php

return [
    'services'  => [
        'Emailer' => function () {
            if (class_exists('\App\Email\Library\Emailer')) {
                return new \App\Email\Library\Emailer();
            } else {
                return new \Nails\Email\Library\Emailer();
            }
        },
    ],
    'factories' => [
        'Email' => function () {
            if (class_exists('\App\Email\Factory\Email')) {
                return new \App\Email\Factory\Email();
            } else {
                return new \Nails\Email\Factory\Email();
            }
        },
    ],
];
