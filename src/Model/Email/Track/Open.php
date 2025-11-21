<?php

namespace Nails\Email\Model\Email\Track;

use Nails\Common\Model\Base;
use Nails\Email\Constants;

/**
 * Class Email
 *
 * @package Nails\Email\Model\Email\Track
 */
class Open extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'email_archive_track_open';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'EmailTrackOpen';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;
}
