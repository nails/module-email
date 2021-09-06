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

    /**
     * Whether to automatically set timestamps or not
     *
     * @var bool
     */
    const AUTO_SET_TIMESTAMP = false;

    /**
     * Whether to automatically set created/modified users or not
     *
     * @var bool
     */
    const AUTO_SET_USER = false;

    // --------------------------------------------------------------------------

    /**
     * Returns an email by it's ref
     *
     * @param string $sRef  The email's ref
     * @param array  $aData Any data to pass to getAll()
     *
     * @return \Nails\Email\Resource\Email|null
     * @throws \Nails\Common\Exception\ModelException
     */
    public function getByRef(string $sRef, array $aData = []): ?\Nails\Email\Resource\Email
    {
        return $this->getByColumn('ref', $sRef, $aData);
    }
}
