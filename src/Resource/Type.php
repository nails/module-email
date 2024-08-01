<?php

/**
 * This class represents objects dispensed by the Emailer service
 *
 * @package  Nails\Email\Resource
 * @category resource
 */

namespace Nails\Email\Resource;

use Nails\Auth\Resource\User;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Factory\Component;
use Nails\Common\Resource;
use Nails\Email\Constants;
use Nails\Email\Service\Emailer;
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

    /** @var Component */
    public $component;

    /** @var string */
    public $description;

    /** @var bool */
    public $is_hidden;

    /** @var bool */
    public $is_editable;

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
     * @return \Nails\Email\Interfaces\Email|null
     * @throws FactoryException
     */
    public function getFactory(): ?\Nails\Email\Interfaces\Email
    {
        if (empty($this->factory)) {
            return null;
        }

        [$sProvider, $sFactory] = explode('::', $this->factory);

        return Factory::factory($sFactory, $sProvider);
    }

    // --------------------------------------------------------------------------
    /**
     * Returns whether a template can be eited/overriden in admin
     *
     * @return bool
     */
    public function isEditable(): bool
    {
        return $this->is_editable;
    }

    // --------------------------------------------------------------------------

    public function isHidden(): bool
    {
        return $this->is_hidden;
    }

    // --------------------------------------------------------------------------

    public function canUnsubscribe(): bool
    {
        return $this->can_unsubscribe;
    }

    // --------------------------------------------------------------------------

    public function isSubscribed(User $user): bool
    {
        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        return !$emailer->userHasUnsubscribed($user, $this->slug);
    }

    // --------------------------------------------------------------------------

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->slug;
    }
}
