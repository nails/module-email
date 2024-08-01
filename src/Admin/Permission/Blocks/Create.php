<?php

namespace Nails\Email\Admin\Permission\Blocks;

use Nails\Admin\Interfaces\Permission;

class Create implements Permission
{
    public function label(): string
    {
        return 'Can create new user email blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
