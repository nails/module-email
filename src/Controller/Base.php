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
use Nails\Common\Constants;
use Nails\Common\Exception\AssetException;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Service\Asset;
use Nails\Factory;

abstract class Base extends \Nails\Common\Controller\Base
{
    /**
     * Loads Auth styles if the supplied view does not exist
     *
     * @param string $sView The view to test
     *
     * @throws FactoryException
     * @throws AssetException
     */
    protected function loadStyles(string $sView)
    {
        //  Test if the app has provided a view
        if (!is_file($sView)) {
            /** @var Asset $oAsset */
            $oAsset = Factory::service('Asset');
            $oAsset
                ->clear()
                ->load('nails.min.css', Constants::MODULE_SLUG)
                ->load('styles.min.css', Auth\Constants::MODULE_SLUG);
        }
    }
}
