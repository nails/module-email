<?php

/**
 * Migration: 13
 * Started:   18/02/2022
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Interfaces;
use Nails\Common\Traits;

/**
 * Class Migration13
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration13 implements Interfaces\Database\Migration
{
    use Traits\Database\Migration;

    // --------------------------------------------------------------------------

    /**
     * Execute the migration
     *
     * @return void
     */
    public function execute(): void
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `queue_process` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `queue_priority`;');
    }
}
