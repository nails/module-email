<?php

namespace Nails\Email\Auth\Admin\User\Tab;

use Nails\Auth\Auth\Admin\User\Tab\Emails;
use Nails\Auth\Interfaces\Admin\User\Tab;
use Nails\Auth\Resource\User;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ViewNotFoundException;
use Nails\Common\Service\View;
use Nails\Email\Constants;
use Nails\Email\Service\Emailer;
use Nails\Factory;

/**
 * Class Subscriptions
 *
 * @package Nails\Email\Auth\Admin\User\Tab
 */
class Subscriptions implements Tab
{
    /**
     * Return the tab's label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return 'Email Subscriptions';
    }

    // --------------------------------------------------------------------------

    public static function isEnabled(User $user): bool
    {
        return userHasPermission('admin:email:subscriptions:browse');
    }

    // --------------------------------------------------------------------------

    /**
     * Return the order in which the tabs should render
     *
     * @return float|null
     */
    public function getOrder(): ?float
    {
        return (new Emails())->getOrder() + 0.1;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the tab's body
     *
     * @param User $oUser The user being edited
     *
     * @return string
     * @throws FactoryException
     * @throws ViewNotFoundException
     */
    public function getBody(User $oUser): string
    {
        /** @var View $view */
        $view = Factory::service('View');
        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        $groups = [];
        foreach ($emailer->getTypes() as $type) {
            if (!array_key_exists($type->component->name, $groups)) {
                $groups[$type->component->name] = [];
            }

            $groups[$type->component->name][] = $type;
        }

        return $view
            ->load(
                [
                    'User/tabs/subscriptions',
                ],
                [
                    'user'   => $oUser,
                    'groups' => $groups,
                ],
                true
            );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns additional markup, outside of the main <form> element
     *
     * @param User $oUser The user being edited
     *
     * @return string
     */
    public function getAdditionalMarkup(User $oUser): string
    {
        return '';
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of validation rules compatible with Validator objects
     *
     * @param User $oUser The user being edited
     *
     * @return array
     */
    public function getValidationRules(User $oUser): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a key/value array of columns and the data to populate
     *
     * @param User  $oUser The user being edited
     * @param array $aPost The POST array
     *
     * @return array
     */
    public function getPostData(User $oUser, array $aPost): array
    {
        return [];
    }
}
