<?php

/**
 * This is a convenience class for generating emails
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Factory
 * @author      Nails Dev Team
 */

namespace Nails\Email\Factory;

use Nails\Email\Interfaces;
use Nails\Email\Traits;

/**
 * Class Email
 *
 * @package    Nails\Email\Factory
 * @deprecated Use Nails\Email\Trait\Email
 */
abstract class Email implements Interfaces\Email
{
    use Traits\Email;
}
