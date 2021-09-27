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

    /**
     * The various email statuses
     */
    const STATUS_PENDING = 'PENDING';
    const STATUS_QUEUED  = 'QUEUED';
    const STATUS_SENDING = 'SENDING';
    const STATUS_SENT    = 'SENT';
    const STATUS_FAILED  = 'FAILED';

    /** @deprecated */
    const STATUS_BOUNCED = 'BOUNCED';
    /** @deprecated */
    const STATUS_OPENED = 'OPENED';
    /** @deprecated */
    const STATUS_REJECTED = 'REJECTED';
    /** @deprecated */
    const STATUS_DELAYED = 'DELAYED';
    /** @deprecated */
    const STATUS_SOFT_BOUNCED = 'SOFT_BOUNCED';
    /** @deprecated */
    const STATUS_MARKED_AS_SPAM = 'MARKED_AS_SPAM';
    /** @deprecated */
    const STATUS_CLICKED = 'CLICKED';

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

    // --------------------------------------------------------------------------

    /**
     * Returns's the various statuses
     *
     * @return string[]
     */
    public function getStatuses(): array
    {
        return [
            static::STATUS_PENDING        => 'Pending',
            static::STATUS_QUEUED         => 'Queued',
            static::STATUS_SENDING        => 'Sending',
            static::STATUS_SENT           => 'Sent',
            static::STATUS_FAILED         => 'Failed',

            // Deprecated
            static::STATUS_BOUNCED        => 'Bounced',
            static::STATUS_OPENED         => 'Opened',
            static::STATUS_REJECTED       => 'Rejected',
            static::STATUS_DELAYED        => 'Delayed',
            static::STATUS_SOFT_BOUNCED   => 'Soft Bounced',
            static::STATUS_MARKED_AS_SPAM => 'Marked as spam',
            static::STATUS_CLICKED        => 'Clicked',
        ];
    }
}
