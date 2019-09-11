<?php

namespace Nails\Email\Model;

use Nails\Common\Model\Base;
use Nails\Email\Constants;

/**
 * Class Email
 *
 * @package Nails\Email\Model
 */
class Email extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'email_archive';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'Email';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;
}
