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
    'models'    => [
        'Email' => function () {
            if (class_exists('\App\Email\Model\Email')) {
                return new \App\Email\Model\Email();
            } else {
                return new \Nails\Email\Model\Email();
            }
        },
    ],
    'resources' => [
        'Email' => function ($mObj) {
            if (class_exists('\App\Email\Resource\Email')) {
                return new \App\Email\Resource\Email($mObj);
            } else {
                return new \Nails\Email\Resource\Email($mObj);
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
