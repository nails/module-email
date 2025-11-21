<?php

namespace Nails\Email\Model\Email;

use Nails\Common\Model\Base;
use Nails\Email\Constants;

/**
 * Class Link
 *
 * @package Nails\Email\Model\Email
 */
class Link extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'email_archive_link';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'EmailLink';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;
}
