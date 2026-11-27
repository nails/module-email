<?php

namespace Nails\Email\Admin\Permission\Settings;

use Nails\Admin\Interfaces\Permission;

class General implements Permission
{
    public function label(): string
    {
        return 'Can manage Email settings';
    }

    public function group(): string
    {
        return 'Settings';
    }
}
