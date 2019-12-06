<?php

namespace Nails\Email;

use Nails\Common\Events\Base;
use Nails\Common\Events\Subscription;
use Nails\Email\Event\Listener;

/**
 * Class Events
 *
 * @package Nails\Email
 */
class Events extends Base
{
    /**
     * Subscribe to events
     *
     * @return Subscription[]
     */
    public function autoload(): array
    {
        return [
            new Listener\Startup(),
        ];
    }
}
