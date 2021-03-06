<?php

/**
 * Migration:   2
 * Started:     06/02/2015
 * Finalised:   06/02/2015
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration2 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `queued` DATETIME  NULL  AFTER `user_email`;");
    }
}

