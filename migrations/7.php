<?php

/**
 * Migration:   7
 * Started:     05/11/2018
 * Finalised:   05/11/2018
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Database\Migration\Nails\ModuleEmail;

use Nails\Common\Console\Migrate\Base;

class Migration7 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` CHANGE `url` `url` varchar(1000) NOT NULL DEFAULT '';");
    }
}
