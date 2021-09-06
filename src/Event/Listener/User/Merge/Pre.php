<?php

namespace Nails\Email\Event\Listener\User\Merge;

use Nails\Auth;
use Nails\Common\Events\Subscription;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Service\Database;
use Nails\Factory;

/**
 * Class Pre
 *
 * @package Nails\Email\Event\Listener\User\Merge
 */
class Pre extends Subscription
{
    /**
     * Pre constructor.
     *
     * @throws FactoryException
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $oModel = Factory::model('User', Auth\Constants::MODULE_SLUG);
        $this
            ->setEvent(Auth\Events::USER_MERGE_PRE)
            ->setNamespace($oModel::getEventNamespace())
            ->setCallback([$this, 'execute']);
    }

    // --------------------------------------------------------------------------

    /**
     * @param int   $iKeepId
     * @param array $aMergeIds
     *
     * @throws FactoryException
     * @throws ModelException
     */
    public function execute(int $iKeepId, array $aMergeIds): void
    {
        $this->deleteEmailBlocks($aMergeIds);
    }

    // --------------------------------------------------------------------------

    /**
     * Before a user is merged delete the merge users block preferences.
     *
     * @param array $aMergeIds
     *
     * @throws FactoryException
     * @throws ModelException
     */
    private function deleteEmailBlocks(array $aMergeIds): void
    {
        /** @var Database $oDb */
        $oDb    = Factory::service('Database');
        $oModel = Factory::model('UserEmailBlocker', Auth\Constants::MODULE_SLUG);

        $oDb->where_in('user_id', $aMergeIds);
        $oDb->delete($oModel->getTableName());
    }
}
