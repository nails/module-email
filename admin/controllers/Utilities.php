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
use Nails\Common\Service\FormValidation;
use Nails\Email\Controller\BaseAdmin;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Service\Emailer;
use Nails\Factory;

class Utilities extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     *
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
     *
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['sendTest'] = 'Can send test email';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Send a test email
     *
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
            try {

                /** @var FormValidation $oFormValidation */
                $oFormValidation = Factory::service('FormValidation');
                $oFormValidation
                    ->buildValidator([
                        'recipient' => ['required', 'valid_email'],
                    ])
                    ->run();

                /** @var Emailer $oEmailer */
                $oEmailer = Factory::service('Emailer', 'nails/module-email');
                $bResult  = $oEmailer->send((object) [
                    'type'     => 'test_email',
                    'to_email' => $oInput->post('recipient'),
                    'data'     => [
                        'sentAt' => Factory::factory('DateTime')->format('Y-m-d H:i:s'),
                    ],
                ]);

                if (!$bResult) {
                    throw new EmailerException(
                        'Failed to send email: ' . $oEmailer->lastError()
                    );
                }

                $this->data['success'] = '<strong>Done!</strong> Test email successfully sent to <strong>';
                $this->data['success'] .= $oInput->post('recipient') . '</strong> at ' . toUserDatetime();

            } catch (\Exception $e) {
                $this->data['error'] = $e->getMessage();
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }
}
