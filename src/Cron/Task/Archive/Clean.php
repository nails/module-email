<?php

/**
 * The Clean Cron task
 *
 * @package  Nails\Email
 * @category Task
 */

namespace Nails\Email\Cron\Task\Archive;

use Nails\Cron\Task\Base;

/**
 * Class Clean
 *
 * @package Nails\Email\Cron\Task\Archive
 */
class Clean extends Base
{
    /**
     * The cron expression of when to run
     *
     * @var string
     */
    const CRON_EXPRESSION = '15 2 * * *';

    /**
     * The console command to execute
     *
     * @var string
     */
    const CONSOLE_COMMAND = 'email:archive:clean';
}
