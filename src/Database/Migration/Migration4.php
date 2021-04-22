<?php

/**
 * Migration:   4
 * Started:     08/12/2015
 * Finalised:   08/12/2015
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration4 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {

        $this->query("DELETE FROM `{{NAILS_DB_PREFIX}}app_setting` WHERE `grouping` = 'email' and `key` != 'from_name' and `key` != 'from_email';");
        /**
         * Convert admin changelog data into JSON strings rather than use serialize
         */

        $oResult = $this->query('SELECT id, email_vars FROM {{NAILS_DB_PREFIX}}email_archive');
        while ($oRow = $oResult->fetch(\PDO::FETCH_OBJ)) {

            $mOldValue = unserialize($oRow->email_vars);
            $sNewValue = json_encode($mOldValue);

            //  Update the record
            $sQuery = '
                UPDATE `{{NAILS_DB_PREFIX}}email_archive`
                SET
                    `email_vars` = :newValue
                WHERE
                    `id` = :id
            ';

            $oSth = $this->prepare($sQuery);

            $oSth->bindParam(':newValue', $sNewValue, \PDO::PARAM_STR);
            $oSth->bindParam(':id', $oRow->id, \PDO::PARAM_INT);

            $oSth->execute();
        }
    }
}
