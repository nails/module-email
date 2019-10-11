<?php

namespace Nails\Email\Model\Template;

use Nails\Common\Model\Base;
use Nails\Email\Constants;

/**
 * Class Override
 *
 * @package Nails\Email\Model
 */
class Override extends Base
{
    /**
     * The table this model represents
     *
     * @var string
     */
    const TABLE = NAILS_DB_PREFIX . 'email_template_override';

    /**
     * The name of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_NAME = 'TemplateOverride';

    /**
     * The provider of the resource to use (as passed to \Nails\Factory::resource())
     *
     * @var string
     */
    const RESOURCE_PROVIDER = Constants::MODULE_SLUG;
}
