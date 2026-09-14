<?php

/**
 * Migration: 14
 * Started:   11/08/2022
 */

namespace Nails\Email\Database\Migration;

use Nails\Admin\Traits\Database\Migration\PermissionMap;
use Nails\Common\Interfaces;
use Nails\Common\Traits;
use Nails\Email\Admin\Permission;

/**
 * Class Migration14
 *
 * Repeatable because `feature/pre-new-admin` has no equivalent migration, so an app
 * arriving from that branch resumes above this number and would never run it.
 *
 * @package Nails\Email\Database\Migration
 */
class Migration14 implements Interfaces\Database\Migration\Repeatable
{
    use Traits\Database\Migration;
    use PermissionMap;

    // --------------------------------------------------------------------------

    const MAP = [
        'admin:email:email:browse'       => Permission\Archive\Browse::class,
        'admin:email:email:resend'       => Permission\Archive\Resend::class,
        'admin:email:blocks:browse'      => Permission\Blocks\Browse::class,
        'admin:email:blocks:create'      => Permission\Blocks\Create::class,
        'admin:email:blocks:edit'        => null,
        'admin:email:blocks:delete'      => Permission\Blocks\Delete::class,
        'admin:email:blocks:restore'     => null,
        'admin:email:templates:edit'     => Permission\Template\Edit::class,
        'admin:email:utilities:sendtest' => Permission\Utilities\SendTest::class,
    ];
}
