<?php

/**
 * This class provides some common email controller functionality
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Email\Controller;

// --------------------------------------------------------------------------
use Nails\Factory;

/**
 * Allow the app to add functionality, if needed
 */
if (class_exists('\App\Email\Controller\Base')) {
    abstract class BaseMiddle extends \App\Email\Controller\Base
    {
    }
} else {
    abstract class BaseMiddle extends \Nails\Common\Controller\Base
    {
    }
}

// --------------------------------------------------------------------------

abstract class Base extends BaseMiddle
{
    /**
     * Loads Auth styles if supplied view does not exist
     *
     * @param string $sView The view to test
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function loadStyles($sView)
    {
        //  Test if a view has been provided by the app
        if (!is_file($sView)) {
            $oAsset = Factory::service('Asset');
            $oAsset->clear();
            $oAsset->load('nails.min.css', 'nails/common');
            $oAsset->load('styles.min.css', 'nails/module-auth');
        }
    }
}
