<?php

//  Include _email.php; executes common functionality
require_once '_email.php';

/**
 * This class allows users to subscribe and unsubscribe from individual email types
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Unsubscribe extends NAILS_Email_Controller
{
    /**
     * Renders the subscribe/unsubscribe page
     * @return void
     */
    public function index()
    {
        if (!$this->user_model->isLoggedIn()) {

            unauthorised();
        }

        $sToken = $this->input->get('token');
        $aToken = $this->encrypt->decode($sToken, APP_PRIVATE_KEY);

        if (!$aToken) {
            show_404();
        }

        $aToken = explode('|', $aToken);

        if (count($aToken) != 3) {
            show_404();
        }

        $oUser = $this->user_model->getById($aToken[2]);
        if (!$oUser || $oUser->id != activeUser('id ')) {
            show_404();
        }

        $oEmail = $this->emailer->getByRef($aToken[1]);

        if (!$oEmail || empty($oEmail->type->isUnsubscribable)) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  All seems above board, action the request
        if ($this->input->get('undo')) {

            if ($this->emailer->userHasUnsubscribed(activeUser('id'), $aToken[0])) {

                $this->emailer->subscribeUser(activeUser('id'), $aToken[0]);
            }

        } else {

            if (!$this->emailer->userHasUnsubscribed(activeUser('id'), $aToken[0])) {

                $this->emailer->unsubscribeUser(activeUser('id'), $aToken[0]);
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('email/utilities/unsubscribe', $this->data);
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

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' EMAIL MODULES
 *
 * The following block of code makes it simple to extend one of the core admin
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION_CLASSNAME
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_UNSUBSCRIBE')) {

    class Unsubscribe extends NAILS_Unsubscribe
    {
    }
}
