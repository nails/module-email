<?php

namespace Nails\Email\Settings;

use Nails\Common\Helper\Form;
use Nails\Common\Interfaces;
use Nails\Common\Service\FormValidation;
use Nails\Components\Setting;
use Nails\Email\Model\Page;
use Nails\Email\Service\Driver;
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
    const KEY_REPLY_TO_EMAIL   = 'reply_to_email';
    const KEY_RETENTION_PERIOD = 'retention_period';
    const KEY_SIGN_OFF         = 'sign_off';
    const KEY_FOOTER_ADDRESS   = 'footer_address';

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

        /** @var Setting $oReplyToEmail */
        $oReplyToEmail = Factory::factory('ComponentSetting');
        $oReplyToEmail
            ->setKey(static::KEY_REPLY_TO_EMAIL)
            ->setLabel('Reply-To Email')
            ->setFieldset('Sender')
            ->setInfo('Set a default reply-to address, if different from the sender.');

        /** @var Setting $oFromEmail */
        $oFromEmail = Factory::factory('ComponentSetting');
        $oFromEmail
            ->setKey(static::KEY_FROM_EMAIL)
            ->setLabel('From Email')
            ->setFieldset('Sender')
            ->setInfo('<strong>Note:</strong> If sending using SMTP to send email ensure this email is a valid account on the mail server. If it\'s not valid, some services will junk the email.');

        /** @var Setting $oSignOff */
        $oSignOff = Factory::factory('ComponentSetting');
        $oSignOff
            ->setKey(static::KEY_SIGN_OFF)
            ->setType(Form::FIELD_TEXTAREA)
            ->setLabel('Sign Off')
            ->setFieldset('Content')
            ->setInfo('Rendered below the body of every email. Mustache variables are available, so <code>The {{appName}} Team</code> and <code>{{sentTo.first_name}}</code> both work. Leave blank to omit it entirely.');

        /** @var Setting $oFooterAddress */
        $oFooterAddress = Factory::factory('ComponentSetting');
        $oFooterAddress
            ->setKey(static::KEY_FOOTER_ADDRESS)
            ->setType(Form::FIELD_TEXTAREA)
            ->setLabel('Footer Address')
            ->setFieldset('Content')
            ->setInfo('A postal address to show in the footer of every email. Leave blank to omit it entirely.');

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
            $oReplyToEmail,
            $oSignOff,
            $oFooterAddress,
            $oRetentionPeriod,
        ];
    }
}
