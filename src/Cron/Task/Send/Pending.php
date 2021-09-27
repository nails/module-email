<?php

/**
 * The Send Pending Cron task
 *
 * @package  Nails\Email
 * @category Task
 */

namespace Nails\Email\Cron\Task\Send;

use Nails\Cron\Task\Base;

/**
 * Class Pending
 *
 * @package Nails\Email\Cron\Task\Send
 */
class Pending extends Base
{
    /**
     * The cron expression of when to run
     *
     * @var string
     */
    const CRON_EXPRESSION = '*/2 * * * *';

    /**
     * The console command to execute
     *
     * @var string
     */
    const CONSOLE_COMMAND = 'email:send:pending';

    /**
     * The maximum number of simultaneous processes which  will be executed
     *
     * @var int
     */
    const MAX_PROCESSES = 5;
}
