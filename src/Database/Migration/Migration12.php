<?php

/**
 * Migration:  12
 * Started:    27/09/2021
 *
 * @package    Nails
 * @subpackage module-email
 * @category   Database Migration
 * @author     Nails Dev Team
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration12
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration12 extends Base
{
    /**
     * Execute the migration
     *
     * @return void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `queued` DATETIME NULL AFTER `user_email`;');
        $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `status` = \'FAILED\' WHERE `status` = \'PENDING\';');
        $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `queued` = `sent` WHERE `queued` IS NULL;');
    }
}
