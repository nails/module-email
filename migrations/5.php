<?php

/**
 * Migration:   5
 * Started:     19/02/2016
 * Finalised:   19/02/2016
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nailsapp\ModuleEmail;

use Nails\Common\Console\Migrate\Base;

class Migration5 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}app_setting` SET `grouping` = 'nailsapp/module-email' WHERE `grouping` = 'email';");
    }
}
