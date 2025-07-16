<?php

/**
 * This class represents objects dispensed by the EmailTrackOpen model
 *
 * @package  Nails\Email\Resource\Email\Track
 * @category resource
 */

namespace Nails\Email\Resource\Email\Track;

use Nails\Common\Resource\Entity;

/**
 * Class Open
 *
 * @package Nails\Email\Resource\Email\Track
 */
class Open extends Entity
{
    /** @var int */
    public $email_id;

    /** @var int|null */
    public $user_id;
}
