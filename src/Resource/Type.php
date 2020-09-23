<?php

/**
 * This class represents objects dispensed by the Emailer service
 *
 * @package  Nails\Email\Resource
 * @category resource
 */

namespace Nails\Email\Resource;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Resource;
use Nails\Factory;

/**
 * Class Type
 *
 * @package Nails\Email\Resource
 */
class Type extends Resource
{
    /** @var string */
    public $slug;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var bool */
    public $is_hidden;

    /** @var bool */
    public $can_unsubscribe;

    /** @var string */
    public $template_header;

    /** @var string */
    public $template_body;

    /** @var string */
    public $template_footer;

    /** @var string */
    public $default_subject;

    /** @var string */
    public $factory;

    // --------------------------------------------------------------------------

    /**
     * Returns the Factory class this email type is represented by, if available
     *
     * @return Nails\Email\Factory\Email|null
     * @throws FactoryException
     */
    public function getFactory(): ?Nails\Email\Factory\Email
    {
        if (empty($this->factory)) {
            return null;
        }

        [$sProvider, $sFactory] = explode('::', $this->factory);

        return Factory::factory($sFactory, $sProvider);
    }
}
