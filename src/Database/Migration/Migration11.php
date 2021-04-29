<?php

/**
 * Migration:  11
 * Started:    29/04/2021
 *
 * @package    Nails
 * @subpackage module-email
 * @category   Database Migration
 * @author     Nails Dev Team
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Console\Migrate\Base;

/**
 * Class Migration11
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration11 extends Base
{
    /**
     * Execute the migration
     *
     * @return void
     */
    public function execute()
    {
        $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `email_vars` `email_vars` JSON NULL;');
    }
}
