<?php

/**
 * This class registers some email based utilities
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Email;

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Email\Controller\BaseAdmin;

class Utilities extends BaseAdmin
{
     /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
        $oNavGroup->setLabel('Utilities');
        $oNavGroup->setIcon('fa-sliders');

        if (userHasPermission('admin:email:utilities:sendTest')) {

            $oNavGroup->addAction('Send Test Email');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['sendTest'] = 'Can send test email';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Send a test email
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:email:utilities:sendTest')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = 'Send a Test Email';

        // --------------------------------------------------------------------------

        if ($this->input->post()) {

            //  Form validation and update
            $oFormValidation = Factory::service('FormValidation');

            //  Define rules
            $oFormValidation->set_rules('recipient', '', 'xss_clean|required|valid_email');

            //  Set Messages
            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

            //  Execute
            if ($oFormValidation->run()) {

                //  Prepare date
                $email           = new \stdClass();
                $email->to_email = $this->input->post('recipient');
                $email->type     = 'test_email';
                $email->data     = array();

                //  Send the email
                if ($this->emailer->send($email)) {

                    $this->data['success']  = '<strong>Done!</strong> Test email successfully sent to <strong>';
                    $this->data['success'] .= $email->to_email . '</strong> at ' . toUserDatetime();

                } else {

                    echo '<h1>Sending Failed, debugging data below:</h1>';
                    echo $this->email->print_debugger();
                    return;
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }
}
