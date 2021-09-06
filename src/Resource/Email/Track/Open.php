<?php

/**
 * This class represents objects dispensed by the EmailTrackOpen model
 *
 * @package  Nails\Email\Resource\Email\Track
 * @category resource
 */

namespace Nails\Email\Resource\Email\Track;

use Nails\Common\Resource;

/**
 * Class Open
 *
 * @package Nails\Email\Resource\Email\Track
 */
class Open extends Resource
{
    /** @var int */
    public $id;

    /** @var int */
    public $email_id;

    /** @var int|null */
    public $user_id;

    /** @var \Nails\Common\Resource\DateTime */
    public $created;
}
