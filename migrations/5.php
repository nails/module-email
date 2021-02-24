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

namespace Nails\Database\Migration\Nails\ModuleEmail;

use Nails\Common\Console\Migrate\Base;
use Nails\Email\Constants;

class Migration5 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("UPDATE `{{NAILS_DB_PREFIX}}app_setting` SET `grouping` = '" . Constants::MODULE_SLUG . "' WHERE `grouping` = 'email';");
    }
}
