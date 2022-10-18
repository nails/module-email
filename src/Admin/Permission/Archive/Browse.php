<?php

namespace Nails\Email\Admin\Permission\Archive;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse the email archive';
    }

    public function group(): string
    {
        return 'Archive';
    }
}
