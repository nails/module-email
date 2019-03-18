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

use Nails\Common\Exception\NailsException;
use Nails\Email\Controller\Base;
use Nails\Factory;

class Verify extends Base
{
    /**
     * Attempt to validate the user's activation code
     */
    public function index()
    {
        $oUri       = Factory::service('Uri');
        $oInput     = Factory::service('Input');
        $oSession   = Factory::service('Session', 'nails/module-auth');
        $oUserModel = Factory::model('User', 'nails/module-auth');

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
                $sRedirect = 'auth/password/reset/' . $oUser->id . '/' . md5($oUser->salt);
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
