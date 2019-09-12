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

namespace Nails\Email\Controller;

use Nails\Admin\Controller\Base;
use Nails\Email\Constants;
use Nails\Factory;

/**
 * Class BaseAdmin
 *
 * @package Nails\Email\Controller
 */
class BaseAdmin extends Base
{
    public function __construct()
    {
        parent::__construct();
        $oAsset = Factory::service('Asset');
        $oAsset->load('admin.min.css', Constants::MODULE_SLUG);
    }
}
