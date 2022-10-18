<?php

namespace Nails\Email\Admin\Permission\Utilities;

use Nails\Admin\Interfaces\Permission;

class SendTest implements Permission
{
    public function label(): string
    {
        return 'Can send test emails';
    }

    public function group(): string
    {
        return 'Utilities';
    }
}
