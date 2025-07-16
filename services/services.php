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
        'Email'            => function ($resource, $model): Resource\Email {
            if (class_exists('\App\Email\Resource\Email')) {
                return new \App\Email\Resource\Email($resource, $model);
            } else {
                return new Resource\Email($resource, $model);
            }
        },
        'EmailLink'        => function ($resource, $model): Resource\Email\Link {
            if (class_exists('\App\Email\Resource\Email\Link')) {
                return new \App\Email\Resource\Email\Link($resource, $model);
            } else {
                return new Resource\Email\Link($resource, $model);
            }
        },
        'EmailTrackLink'   => function ($resource, $model): Resource\Email\Track\Link {
            if (class_exists('\App\Email\Resource\Email\Track\Link')) {
                return new \App\Email\Resource\Email\Track\Link($resource, $model);
            } else {
                return new Resource\Email\Track\Link($resource, $model);
            }
        },
        'EmailTrackOpen'   => function ($resource, $model): Resource\Email\Track\Open {
            if (class_exists('\App\Email\Resource\Email\Track\Open')) {
                return new \App\Email\Resource\Email\Track\Open($resource, $model);
            } else {
                return new Resource\Email\Track\Open($resource, $model);
            }
        },
        'TemplateOverride' => function ($resource, $model): Resource\Template\Override {
            if (class_exists('\App\Email\Resource\Template\Override')) {
                return new \App\Email\Resource\Template\Override($resource, $model);
            } else {
                return new Resource\Template\Override($resource, $model);
            }
        },
        'Type'             => function ($resource, $model = null): Resource\Type {
            //  @todo (Pablo 2025-07-15) - this should be a factory
            if (class_exists('\App\Email\Resource\Type')) {
                return new \App\Email\Resource\Type($resource);
            } else {
                return new Resource\Type($resource);
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
