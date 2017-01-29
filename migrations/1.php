<?php

/**
 * Migration:   1
 * Started:     04/02/2015
 * Finalised:   04/02/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleEmail;

use Nails\Common\Console\Migrate\Base;

class Migration1 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `email_vars` `email_vars` LONGTEXT  CHARACTER SET utf8mb4  NULL;");
    }
}
