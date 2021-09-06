<?php

use Nails\Email\Service;
use Nails\Email\Model;
use Nails\Email\Resource;
use Nails\Email\Factory;

return [
    'services'  => [
        'Emailer' => function (): Service\Emailer {
            if (class_exists('\App\Email\Service\Emailer')) {
                return new \App\Email\Service\Emailer();
            } else {
                return new Service\Emailer();
            }
        },
    ],
    'models'    => [
        'Email'            => function (): Model\Email {
            if (class_exists('\App\Email\Model\Email')) {
                return new \App\Email\Model\Email();
            } else {
                return new Model\Email();
            }
        },
        'EmailLink'        => function (): Model\Email\Link {
            if (class_exists('\App\Email\Model\Email\Link')) {
                return new \App\Email\Model\Email\Link();
            } else {
                return new Model\Email\Link();
            }
        },
        'EmailTrackLink'   => function (): Model\Email\Track\Link {
            if (class_exists('\App\Email\Model\Email\Track\Link')) {
                return new \App\Email\Model\Email\Track\Link();
            } else {
                return new Model\Email\Track\Link();
            }
        },
        'EmailTrackOpen'   => function (): Model\Email\Track\Open {
            if (class_exists('\App\Email\Model\Email\Track\Open')) {
                return new \App\Email\Model\Email\Track\Open();
            } else {
                return new Model\Email\Track\Open();
            }
        },
        'TemplateOverride' => function (): Model\Template\Override {
            if (class_exists('\App\Email\Model\Template\Override')) {
                return new \App\Email\Model\Template\Override();
            } else {
                return new Model\Template\Override();
            }
        },
    ],
    'resources' => [
        'Email'            => function ($mObj): Resource\Email {
            if (class_exists('\App\Email\Resource\Email')) {
                return new \App\Email\Resource\Email($mObj);
            } else {
                return new Resource\Email($mObj);
            }
        },
        'EmailLink'        => function ($mObj): Resource\Email\Link {
            if (class_exists('\App\Email\Resource\Email\Link')) {
                return new \App\Email\Resource\Email\Link($mObj);
            } else {
                return new Resource\Email\Link($mObj);
            }
        },
        'EmailTrackLink'   => function ($mObj): Resource\Email\Track\Link {
            if (class_exists('\App\Email\Resource\Email\Track\Link')) {
                return new \App\Email\Resource\Email\Track\Link($mObj);
            } else {
                return new Resource\Email\Track\Link($mObj);
            }
        },
        'EmailTrackOpen'   => function ($mObj): Resource\Email\Track\Open {
            if (class_exists('\App\Email\Resource\Email\Track\Open')) {
                return new \App\Email\Resource\Email\Track\Open($mObj);
            } else {
                return new Resource\Email\Track\Open($mObj);
            }
        },
        'TemplateOverride' => function ($mObj): Resource\Template\Override {
            if (class_exists('\App\Email\Resource\Template\Override')) {
                return new \App\Email\Resource\Template\Override($mObj);
            } else {
                return new Resource\Template\Override($mObj);
            }
        },
        'Type'             => function ($mObj): Resource\Type {
            if (class_exists('\App\Email\Resource\Type')) {
                return new \App\Email\Resource\Type($mObj);
            } else {
                return new Resource\Type($mObj);
            }
        },
    ],
    'factories' => [
        'EmailTest' => function (): Factory\Email\Test {
            if (class_exists('\App\Email\Factory\Email\Test')) {
                return new \App\Email\Factory\Email\Test();
            } else {
                return new Factory\Email\Test();
            }
        },
    ],
];
