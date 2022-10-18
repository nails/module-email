<?php

/**
 * Migration: 14
 * Started:   11/08/2022
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Interfaces;
use Nails\Common\Traits;
use Nails\Email\Admin\Permission;

/**
 * Class Migration14
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration14 implements Interfaces\Database\Migration
{
    use Traits\Database\Migration;

    // --------------------------------------------------------------------------

    const MAP = [
        'admin:email:email:browse'       => Permission\Archive\Browse::class,
        'admin:email:email:resend'       => Permission\Archive\Resend::class,
        'admin:email:templates:edit'     => Permission\Template\Edit::class,
        'admin:email:utilities:sendtest' => Permission\Utilities\SendTest::class,
    ];

    // --------------------------------------------------------------------------

    /**
     * Execute the migration
     */
    public function execute(): void
    {
        //  On a fresh build, this table might not yet exist
        $oResult = $this->query('SHOW TABLES LIKE "{{NAILS_DB_PREFIX}}user_group"');
        if ($oResult->rowCount() === 0) {
            return;
        }

        $oResult = $this->query('SELECT id, acl FROM `{{NAILS_DB_PREFIX}}user_group`');
        while ($row = $oResult->fetchObject()) {

            $acl = json_decode($row->acl) ?? [];

            foreach ($acl as &$old) {
                $old = self::MAP[$old] ?? $old;
            }

            $acl = array_filter($acl);
            $acl = array_unique($acl);
            $acl = array_values($acl);

            $this
                ->prepare('UPDATE `{{NAILS_DB_PREFIX}}user_group` SET `acl` = :acl WHERE `id` = :id')
                ->execute([
                    ':id'  => $row->id,
                    ':acl' => json_encode($acl),
                ]);
        }
    }
}
