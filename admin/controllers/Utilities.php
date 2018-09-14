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

use Nails\Admin\Helper;
use Nails\Email\Controller\BaseAdmin;
use Nails\Factory;

class Utilities extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
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
        $aPermissions = parent::permissions();

        $aPermissions['sendTest'] = 'Can send test email';

        return $aPermissions;
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

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            //  Form validation and update
            $oFormValidation = Factory::service('FormValidation');

            //  Define rules
            $oFormValidation->set_rules('recipient', '', 'required|valid_email');

            //  Set Messages
            $oFormValidation->set_message('required', lang('fv_required'));
            $oFormValidation->set_message('valid_email', lang('fv_valid_email'));

            //  Execute
            if ($oFormValidation->run()) {

                //  Prepare data
                $oNow   = Factory::factory('DateTime');
                $oEmail = (object) [
                    'type'     => 'test_email',
                    'to_email' => $oInput->post('recipient', true),
                    'data'     => [
                        'sentAt' => $oNow->format('Y-m-d H:i:s'),
                    ],
                ];

                //  Send the email
                $oEmailer = Factory::service('Emailer', 'nails/module-email');
                if ($oEmailer->send($oEmail)) {

                    $this->data['success'] = '<strong>Done!</strong> Test email successfully sent to <strong>';
                    $this->data['success'] .= $oEmail->to_email . '</strong> at ' . toUserDatetime();

                } else {

                    echo '<h1>Sending Failed, debugging data below:</h1>';
                    echo $oEmailer->print_debugger();
                    return;
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }
}
