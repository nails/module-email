<?php

/**
 * Migration:   10
 * Started:     29/10/2020
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nails\ModuleEmail;

use Nails\Common\Console\Migrate\Base;

class Migration10 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `status` `status` ENUM(\'PENDING\',\'SENT\',\'BOUNCED\',\'OPENED\',\'REJECTED\',\'DELAYED\',\'SOFT_BOUNCED\',\'MARKED_AS_SPAM\',\'CLICKED\',\'FAILED\') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT \'PENDING\';');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `fail_reason` TEXT  NULL  AFTER `link_click_count`;');
    }
}
