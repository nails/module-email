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

use Nails\Admin\Controller\Base;
use Nails\Admin\Factory\Nav;
use Nails\Admin\Helper;
use Nails\Auth\Model\User;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Service\FormValidation;
use Nails\Common\Service\Input;
use Nails\Email\Constants;
use Nails\Factory;

/**
 * Class Utilities
 *
 * @package Nails\Admin\Email
 */
class Utilities extends Base
{
    /**
     * Announces this controller's navGroups
     */
    public static function announce(): Nav|array|null
    {
        /** @var Nav $oNavGroup */
        $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
        $oNavGroup->setLabel('Utilities');

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

        /** @var \Nails\Email\Service\Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var \Nails\Common\Service\Input $oInput */
        $oInput = Factory::service('Input');

        // --------------------------------------------------------------------------

        $this->data['page']->title = 'Send a Test Email';
        $this->data['aTypes']      = $oEmailer->getTypesFlat();

        // --------------------------------------------------------------------------

        if ($oInput->post()) {
            try {

                /** @var FormValidation $oFormValidation */
                $oFormValidation = Factory::service('FormValidation');
                $oFormValidation
                    ->buildValidator([
                        'recipient' => [
                            FormValidation::RULE_REQUIRED,
                            FormValidation::RULE_VALID_EMAIL,
                        ],
                        'type'      => [
                            FormValidation::RULE_REQUIRED,
                            function ($sType) use ($oEmailer) {

                                $type = $oEmailer->getType($sType);

                                if (empty($type)) {
                                    throw new ValidationException('Invalid selection');
                                } elseif (empty($type->factory)) {
                                    throw new ValidationException('Cannot test this type of email');
                                }

                                try {
                                    $oFactory = $type->getFactory();
                                } catch (\Exception $e) {
                                    throw new ValidationException('Cannot test this type of email');
                                }

                                /** @var Input $input */
                                $input = Factory::service('Input');
                                /** @var User $userModel */
                                $userModel = Factory::model('User', \Nails\Auth\Constants::MODULE_SLUG);
                                $user      = $userModel->getByEmail($input->post('recipient'));
                                if ($user && $oEmailer->userHasUnsubscribed($user, $type)) {
                                    throw new ValidationException('User has unsubscribed from this type of email');
                                }
                            },
                        ],
                    ])
                    ->run();

                $oType  = $oEmailer->getType($oInput->post('type'));
                $oEmail = $oType->getFactory();
                $oEmail
                    ->to($oInput->post('recipient'))
                    ->data($oEmail->getTestData())
                    ->send();

                $aEmails = $oEmail->getGeneratedEmails();

                $this->oUserFeedback
                    ->success(sprintf(
                        'Test email successfully sent to <strong>%s</strong> at %s.%s%s',
                        $oInput->post('recipient'),
                        toUserDatetime(),
                        !empty($aEmails) ? '<br>The following email was generated: ' : '',
                        implode(', ', array_map(
                            fn($email) => anchor($email->data->url->viewOnline, $email->data->emailRef, 'target="_blank"'),
                            $aEmails
                        ))
                    ));

            } catch (\Exception $e) {
                $this->oUserFeedback->error($e->getMessage());
            }
        }

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }
}
