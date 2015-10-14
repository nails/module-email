<?php

/**
 * This class provides some common Email controller functionality in admin
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\email\Controller;

use Nails\Admin\Controller\Base;

class BaseAdmin extends Base
{
    public function __construct()
    {
        parent::__construct();
        $this->asset->load('nails.admin.module.email.css', 'NAILS');
    }
}
