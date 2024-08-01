<?php

namespace Nails\Email\Admin\Permission\Blocks;

use Nails\Admin\Interfaces\Permission;

class Delete implements Permission
{
    public function label(): string
    {
        return 'Can delete new user email blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
