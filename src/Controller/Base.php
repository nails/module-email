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
}
