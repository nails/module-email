<?php

/**
 * This class represents objects dispensed by the EmailTrackLink model
 *
 * @package  Nails\Email\Resource\Email\Track
 * @category resource
 */

namespace Nails\Email\Resource\Email\Track;

use Nails\Common\Resource\Entity;

/**
 * Class Link
 *
 * @package Nails\Email\Resource\Email\Track
 */
class Link extends Entity
{
    /** @var int */
    public $email_id;

    /** @var int */
    public $link_id;

    /** @var int|null */
    public $user_id;
}
