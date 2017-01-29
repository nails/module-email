<?php

/**
 * Migration:   3
 * Started:     26/02/2015
 * Finalised:   26/02/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleEmail;

use Nails\Common\Console\Migrate\Base;

class Migration3 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` DROP `queued`;");
    }
}
