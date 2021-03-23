<?php

/**
 * This class allows users to verify their email address
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Auth;
use Nails\Common\Exception\NailsException;
use Nails\Common\Service\Input;
use Nails\Common\Service\Session;
use Nails\Common\Service\Uri;
use Nails\Email\Controller\Base;
use Nails\Factory;

/**
 * Class Verify
 */
class Verify extends Base
{
    /**
     * Attempt to validate the user's activation code
     */
    public function index()
    {
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Input $oInput */
        $oInput = Factory::service('Input');
        /** @var Session $oSession */
        $oSession = Factory::service('Session');
        /** @var Auth\Model\User $oUserModel */
        $oUserModel = Factory::model('User', Auth\Constants::MODULE_SLUG);
        /** @var Auth\Model\User\Password $oPasswordModel */
        $oPasswordModel = Factory::model('UserPassword', Auth\Constants::MODULE_SLUG);

        $iId      = $oUri->segment(3);
        $sCode    = $oUri->segment(4);
        $sStatus  = '';
        $sMessage = '';
        $oUser    = $oUserModel->getById($iId);

        if ($oUser && !$oUser->email_is_verified && $sCode) {

            try {

                if (!$oUserModel->emailVerify($oUser->id, $sCode)) {
                    throw new NailsException($oUserModel->lastError());
                }

                //  Reward referrer (if any)
                if (!empty($oUser->referred_by)) {
                    $oUserModel->rewardReferral($oUser->id, $oUser->referred_by);
                }

                $sStatus  = 'success';
                $sMessage = 'Success! Email verified successfully, thanks!';

            } catch (\Exception $e) {
                $sStatus  = 'error';
                $sMessage = 'Sorry, we couldn\'t verify your email address. ' . $e->getMessage();
            }
        }

        // --------------------------------------------------------------------------

        if ($oInput->get('return_to')) {
            $sRedirect = $oInput->get('return_to');

        } elseif (!isLoggedIn() && $oUser) {
            if ($oUser->temp_pw) {
                $sRedirect = $oPasswordModel::resetUrl($oUser);

            } else {
                $oUserModel->setLoginData($oUser->id);
                $sRedirect = $oUser->group_homepage;
            }

        } elseif ($oUser) {
            $sRedirect = $oUser->group_homepage;

        } else {
            $sRedirect = '/';
        }

        if (!empty($sStatus)) {
            $oSession->setFlashData(
                $sStatus,
                $sMessage
            );
        }
        redirect($sRedirect);
    }

    // --------------------------------------------------------------------------

    /**
     *  Map the class so that index() does all the work
     */
    public function _remap()
    {
        $this->index();
    }
}
