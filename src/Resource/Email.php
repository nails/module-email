<?php

/**
 * This class represents objects dispensed by the Email model
 *
 * @package  Nails\Email\Resource
 * @category resource
 */

namespace Nails\Email\Resource;

use Nails\Common\Resource;

/**
 * Class Email
 *
 * @package Nails\Email\Resource
 */
class Email extends Resource
{
    /** @var int */
    public $id;

    /** @var string */
    public $ref;

    /** @var int|null */
    public $user_id;

    /** @var string|null */
    public $user_email;

    /** @var \Nails\Common\Resource\DateTime */
    public $sent;

    /** @var string */
    public $status;

    /** @var string */
    public $type;

    /** @var string|null */
    public $email_vars;

    /** @var string|null */
    public $internal_ref;

    /** @var int */
    public $read_count;

    /** @var int */
    public $link_click_count;

    /** @var string|null */
    public $fail_reason;
}
