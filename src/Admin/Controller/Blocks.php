<?php

/**
 * This class manages email blocks
 *
 * @package    Nails
 * @subpackage module-email
 * @category   AdminController
 * @author     Nails Dev Team
 */

namespace Nails\Email\Admin\Controller;

use Nails\Admin\Controller\DefaultController;
use Nails\Admin\Factory\IndexFilter;
use Nails\Admin\Factory\IndexFilter\Option;
use Nails\Auth;
use Nails\Email\Constants;
use Nails\Email\Resource\Type;
use Nails\Email\Service\Emailer;
use Nails\Factory;

/**
 * Class Blocks
 *
 * @package Nails\Admin\Email
 */
class Blocks extends DefaultController
{
    const CONFIG_MODEL_NAME     = 'UserEmailBlocker';
    const CONFIG_MODEL_PROVIDER = Auth\Constants::MODULE_SLUG;
    const CONFIG_SIDEBAR_GROUP  = 'Email';
    const CONFIG_TITLE_SINGLE   = 'Block';
    const CONFIG_INDEX_FIELDS   = [
        'User'    => 'user_id',
        'Type'    => null,
        'Created' => 'created',
    ];
    const CONFIG_SORT_OPTIONS   = [
        'Created' => 'created',
        'Type'    => 'type',
    ];
    const CONFIG_SORT_DIRECTION = self::SORT_DESCENDING;
    const CONFIG_CAN_EDIT       = false;
    const CONFIG_PERMISSION     = 'email:blocks';
    const CHANGELOG_ENABLED     = false;

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();

        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        $this->aConfig['INDEX_FIELDS']['Type'] = function (Auth\Resource\User\Email\Blocker $row) use ($emailer) {

            $type        = $emailer->getType($row->type);
            $name        = $type->name ?? '';
            $description = $type->description ?? '';
            $component   = $type->component->name ?? '';

            return <<<EOT
            <span class="hint--top" aria-label="{$description}">{$name}</span>
            <small>Provided by <code>{$component}</code></small>
            EOT;
        };
    }

    // --------------------------------------------------------------------------

    protected function indexDropdownFilters(): array
    {
        /** @var Emailer $emailer */
        $emailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var IndexFilter $typeFilter */
        $typeFilter = Factory::factory('IndexFilter', \Nails\Admin\Constants::MODULE_SLUG);
        /** @var Option $filterOption */
        $filterOption = Factory::factory('IndexFilterOption', \Nails\Admin\Constants::MODULE_SLUG);

        $typeFilter
            ->setLabel('Type')
            ->setColumn('type')
            ->addOptions(
                array_merge(
                    [
                        (clone $filterOption)
                            ->setLabel('All'),
                    ],
                    array_map(
                        fn(Type $type) => (clone $filterOption)
                            ->setLabel($type->name)
                            ->setValue($type->slug),
                        $emailer->getTypes()
                    )
                )
            );

        return array_merge(
            parent::indexDropdownFilters(),
            [$typeFilter]
        );
    }
}
