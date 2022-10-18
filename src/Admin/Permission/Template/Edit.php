<?php

namespace Nails\Email\Admin\Permission\Template;

use Nails\Admin\Interfaces\Permission;

class Edit implements Permission
{
    public function label(): string
    {
        return 'Can edit email templates';
    }

    public function group(): string
    {
        return 'Templates';
    }
}
