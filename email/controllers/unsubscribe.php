<?php

/**
 * This class allows users to subscribe and unsubscribe from individual email types
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Email\Controller\Base;
use Nails\Factory;

class Unsubscribe extends Base
{
    /**
     * Renders the subscribe/unsubscribe page
     * @return void
     */
    public function index()
    {
        if (!isLoggedIn()) {
            unauthorised();
        }

        $oInput     = Factory::service('Input');
        $oEncrypt   = Factory::service('Encrypt');
        $oEmailer   = Factory::service('Emailer', 'nailsapp/module-email');
        $oUserModel = Factory::model('User', 'nailsapp/module-auth');

        $sToken = $oInput->get('token');
        $aToken = $oEncrypt->decode($sToken, APP_PRIVATE_KEY);

        if (!$aToken) {
            show_404();
        }

        $aToken = explode('|', $aToken);

        if (count($aToken) != 3) {
            show_404();
        }

        $oUser = $oUserModel->getById($aToken[2]);
        if (!$oUser || $oUser->id != activeUser('id ')) {
            show_404();
        }

        $oEmail = $oEmailer->getByRef($aToken[1]);

        if (!$oEmail || empty($oEmail->type->isUnsubscribable)) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  All seems above board, action the request
        if ($oInput->get('undo')) {
            if ($oEmailer->userHasUnsubscribed(activeUser('id'), $aToken[0])) {
                $oEmailer->subscribeUser(activeUser('id'), $aToken[0]);
            }
        } else {
            if (!$oEmailer->userHasUnsubscribed(activeUser('id'), $aToken[0])) {
                $oEmailer->unsubscribeUser(activeUser('id'), $aToken[0]);
            }
        }

        //  Load views
        $oView = Factory::service('View');
        $oView->load('email/utilities/unsubscribe', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Map all requests to index
     * @return  void
     **/
    public function _remap()
    {
        $this->index();
    }
}
