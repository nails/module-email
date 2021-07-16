<?php

namespace Nails\Email\Event\Listener\User\Merge;

use Nails\Auth;
use Nails\Common\Events\Subscription;
use Nails\Email\Constants;
use Nails\Factory;
use Nails\Common\Exception\FactoryException;

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
     */
    private function deleteEmailBlocks(array $aMergeIds): void
    {
        /** @var \Nails\Common\Service\Database $oDb */
        $oDb = Factory::service('Database');
        /** @var \Nails\Email\Service\Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        $oDb->where_in('user_id', $aMergeIds);
        $oDb->delete($oEmailer->getEmailBlockerTableName());
    }
}
