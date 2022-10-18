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

namespace Nails\Email\Admin\Controller;

use Nails\Admin\Controller\Base;
use Nails\Admin\Helper;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Service\FormValidation;
use Nails\Email\Admin\Permission;
use Nails\Email\Constants;
use Nails\Email\Resource\Type;
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
     *
     * @return stdClass
     */
    public static function announce()
    {
        /** @var \Nails\Admin\Factory\Nav $oNavGroup */
        $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
        $oNavGroup->setLabel('Utilities');

        if (userHasPermission(Permission\Utilities\SendTest::class)) {
            $oNavGroup->addAction('Send Test Email');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Send a test email
     *
     * @return void
     */
    public function index()
    {
        if (!userHasPermission(Permission\Utilities\SendTest::class)) {
            unauthorised();
        }

        /** @var \Nails\Email\Service\Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var \Nails\Common\Service\Input $oInput */
        $oInput = Factory::service('Input');

        // --------------------------------------------------------------------------

        $this->setTitles(['Email', 'Send a test']);
        $this->data['aTypes'] = $oEmailer->getTypesFlat();

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

                                $oType = $oEmailer->getType($sType);

                                if (empty($oType)) {
                                    throw new ValidationException('Invalid selection');
                                } elseif (empty($oType->factory)) {
                                    throw new ValidationException('Cannot test this type of email');
                                }

                                try {
                                    $oFactory = $oType->getFactory();
                                } catch (\Exception $e) {
                                    throw new ValidationException('Cannot test this type of email');
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

                $this->oUserFeedback->success(sprintf(
                    '<strong>Done!</strong> Test email successfully sent to <strong>%s</strong> at %s.',
                    $oInput->post('recipient'),
                    toUserDatetime()
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
