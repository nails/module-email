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

use Nails\Auth;
use Nails\Common\Exception\NailsException;
use Nails\Factory;

/**
 * Allow the app to add functionality, if needed
 * Negative conditional helps with static analysis
 */
if (!class_exists('\App\Email\Controller\Base')) {
    abstract class BaseMiddle extends \App\Controller\Base
    {
    }
} else {
    abstract class BaseMiddle extends \App\Email\Controller\Base
    {
        public function __construct()
        {
            if (!classExtends(parent::class, \App\Controller\Base::class)) {
                throw new NailsException(sprintf(
                    'Class %s must extend %s',
                    parent::class,
                    \App\Controller\Base::class
                ));
            }
            parent::__construct();
        }
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
            $oAsset->load('nails.min.css', \Nails\Common\Constants::MODULE_SLUG);
            $oAsset->load('styles.min.css', Auth\Constants::MODULE_SLUG);
        }
    }
}
