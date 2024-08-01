<?php

namespace Nails\Email\Admin\Permission\Blocks;

use Nails\Admin\Interfaces\Permission;

class Browse implements Permission
{
    public function label(): string
    {
        return 'Can browse the user email blocks';
    }

    public function group(): string
    {
        return 'Blocks';
    }
}
