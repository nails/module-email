<?php

return [
    'services'  => [
        'Emailer' => function () {
            if (class_exists('\App\Email\Service\Emailer')) {
                return new \App\Email\Service\Emailer();
            } else {
                return new \Nails\Email\Service\Emailer();
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
