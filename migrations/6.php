<?php

/**
 * Migration:   6
 * Started:     20/07/2017
 * Finalised:   20/07/2017
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nails\ModuleEmail;

use Nails\Common\Console\Migrate\Base;

class Migration6 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `read_count` `read_count` INT(11) UNSIGNED NOT NULL DEFAULT 0;');
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `link_click_count` `link_click_count` INT(11) UNSIGNED NOT NULL DEFAULT 0;');
    }
}
