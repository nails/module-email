<?php

namespace Nails\Email\Settings;

use Nails\Common\Service\FormValidation;
use Nails\Email\Model\Page;
use Nails\Email\Service\Driver;
use Nails\Common\Helper\Form;
use Nails\Common\Interfaces;
use Nails\Components\Setting;
use Nails\Email\Constants;
use Nails\Factory;

/**
 * Class General
 *
 * @package Nails\Email\Settings
 */
class General implements Interfaces\Component\Settings
{
    const KEY_FROM_NAME        = 'from_name';
    const KEY_FROM_EMAIL       = 'from_email';
    const KEY_RETENTION_PERIOD = 'retention_period';

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getLabel(): string
    {
        return 'Email';
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getPermissions(): array
    {
        return [];
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        /** @var Setting $oFromName */
        $oFromName = Factory::factory('ComponentSetting');
        $oFromName
            ->setKey(static::KEY_FROM_NAME)
            ->setLabel('From Name')
            ->setFieldset('Sender');

        /** @var Setting $oFromEmail */
        $oFromEmail = Factory::factory('ComponentSetting');
        $oFromEmail
            ->setKey(static::KEY_FROM_EMAIL)
            ->setLabel('From Email')
            ->setFieldset('Sender')
            ->setInfo('<strong>Note:</strong> If sending using SMTP to send email ensure this email is a valid account on the mail server. If it\'s not valid, some services will junk the email.');

        /** @var Setting $oRetentionPeriod */
        $oRetentionPeriod = Factory::factory('ComponentSetting');
        $oRetentionPeriod
            ->setKey(static::KEY_RETENTION_PERIOD)
            ->setType(Form::FIELD_NUMBER)
            ->setLabel('Days')
            ->setFieldset('Data Retention')
            ->setInfo('This number defines how long emails should be kept in the archive, set to 0 to disable archive cleanup')
            ->addValidation(FormValidation::RULE_IS_NATURAL);

        return [
            $oFromName,
            $oFromEmail,
            $oRetentionPeriod
        ];
    }
}
