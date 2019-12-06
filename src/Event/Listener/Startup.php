<?php

namespace Nails\Email\Event\Listener;

use Nails\Common\Events;
use Nails\Common\Events\Subscription;
use Nails\Common\Exception\NailsException;
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
        Functions::define('APP_DEVELOPER_EMAIL', '');
        Functions::define('EMAIL_OVERRIDE', '');
        Functions::define('EMAIL_WHITELIST', '');

        //  Specify these first for backwards compatability
        //  @todo (Pablo - 2019-12-06) - Remove these
        Functions::define('DEPLOY_EMAIL_HOST', '127.0.0.1');
        Functions::define('DEPLOY_EMAIL_USER', null);
        Functions::define('DEPLOY_EMAIL_PASS', null);
        Functions::define('DEPLOY_EMAIL_PORT', 25);

        Functions::define('EMAIL_HOST', DEPLOY_EMAIL_HOST);
        Functions::define('EMAIL_USERNAME', DEPLOY_EMAIL_USER);
        Functions::define('EMAIL_PASSWORD', DEPLOY_EMAIL_PASS);
        Functions::define('EMAIL_PORT', DEPLOY_EMAIL_PORT);
    }
}
