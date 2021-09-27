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
        $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `queued` = `sent` WHERE `queued` IS NULL;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `queue_priority` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `queued`;');
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `status` `status` ENUM('PENDING','QUEUED','SENDING','SENT','BOUNCED','OPENED','REJECTED','DELAYED','SOFT_BOUNCED','MARKED_AS_SPAM','CLICKED','FAILED') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'PENDING';");
    }
}
