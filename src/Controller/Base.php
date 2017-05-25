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

class Base extends \App\Controller\Base
{
    /**
     * Base constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load( 'email' );
    }
}
