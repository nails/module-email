<?php

namespace Nails\Email\Event\Listener;

use Nails\Common\Events;
use Nails\Common\Events\Subscription;
use Nails\Common\Exception\NailsException;
use Nails\Config;
use Nails\Functions;
use ReflectionException;

/**
 * Class Startup
 *
 * @package Nails\Email\Event\Listener
 */
class Startup extends Subscription
{
    /**
     * Startup constructor.
     *
     * @throws NailsException
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this->setEvent(Events::SYSTEM_STARTUP)
            ->setNamespace(Events::getEventNamespace())
            ->setCallback([$this, 'execute']);
    }

    // --------------------------------------------------------------------------

    /**
     * Define email constants
     */
    public function execute()
    {
        //  @todo (Pablo - 2019-12-06) - Remove this once a unified settings system is in place
        //  Email constants
        Config::default('APP_DEVELOPER_EMAIL', '');
        Config::default('EMAIL_OVERRIDE', '');
        Config::default('EMAIL_WHITELIST', '');

        //  Specify these first for backwards compatability
        //  @todo (Pablo - 2019-12-06) - Remove these
        Config::default('DEPLOY_EMAIL_HOST', '127.0.0.1');
        Config::default('DEPLOY_EMAIL_USER', null);
        Config::default('DEPLOY_EMAIL_PASS', null);
        Config::default('DEPLOY_EMAIL_PORT', 25);

        Config::default('EMAIL_HOST', '127.0.0.1');
        Config::default('EMAIL_USERNAME', null);
        Config::default('EMAIL_PASSWORD', null);
        Config::default('EMAIL_PORT', 25);
    }
}
