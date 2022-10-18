<?php

namespace Nails\Email\Admin\Permission\Archive;

use Nails\Admin\Interfaces\Permission;

class Resend implements Permission
{
    public function label(): string
    {
        return 'Can re-send emails';
    }

    public function group(): string
    {
        return 'Archive';
    }
}
