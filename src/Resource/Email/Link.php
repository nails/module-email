<?php

/**
 * This class represents objects dispensed by the EmailLink model
 *
 * @package  Nails\Email\Resource\Email
 * @category resource
 */

namespace Nails\Email\Resource\Email;

use Nails\Common\Resource;

/**
 * Class Link
 *
 * @package Nails\Email\Resource\Email
 */
class Link extends Resource
{
    /** @var int */
    public $id;

    /** @var int */
    public $email_id;

    /** @var string */
    public $url;

    /** @var string */
    public $title;

    /** @var \Nails\Common\Resource\DateTime */
    public $created;

    /** @var bool */
    public $is_html;
}
