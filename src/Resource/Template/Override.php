<?php

/**
 * This class represents objects dispensed by the Override model
 *
 * @package  Nails\Email\Resource\Template
 * @category resource
 */

namespace Nails\Email\Resource\Template;

use Nails\Common\Resource\Entity;

/**
 * Class Override
 *
 * @package Nails\Email\Resource\Template
 */
class Override extends Entity
{
    /** @var string|null */
    public $slug;

    /** @var string|null */
    public $subject;

    /** @var string|null */
    public $subject_original_hash;

    /** @var string|null */
    public $body_html;

    /** @var string|null */
    public $body_html_original_hash;

    /** @var string|null */
    public $body_text;

    /** @var string|null */
    public $body_text_original_hash;

}
