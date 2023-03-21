<?php

/**
 * This class brings email functionality to Nails
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Library
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Email\Service;

use Nails\Auth;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Factory\Component;
use Nails\Common\Helper\Model\Where;
use Nails\Common\Service\Database;
use Nails\Common\Service\Encrypt;
use Nails\Common\Service\Event;
use Nails\Common\Service\Input;
use Nails\Common\Service\Mustache;
use Nails\Common\Traits\ErrorHandling;
use Nails\Common\Traits\GetCountCommon;
use Nails\Components;
use Nails\Config;
use Nails\Email\Constants;
use Nails\Email\Events;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Exception\HostNotKnownException;
use Nails\Email\Model\Email;
use Nails\Email\Resource\Template\Override;
use Nails\Email\Resource\Type;
use Nails\Environment;
use Nails\Factory;
use PHPMailer\PHPMailer;
use stdClass;

/**
 * Class Emailer
 *
 * @package Nails\Email\Service
 */
class Emailer
{
    use ErrorHandling;
    use GetCountCommon;

    // --------------------------------------------------------------------------

    /** @deprecated */
    const STATUS_PENDING = Email::STATUS_PENDING;
    /** @deprecated */
    const STATUS_QUEUED = Email::STATUS_QUEUED;
    /** @deprecated */
    const STATUS_SENDING = EMAIL::STATUS_SENDING;
    /** @deprecated */
    const STATUS_SENT = EMAIL::STATUS_SENT;
    /** @deprecated */
    const STATUS_FAILED = EMAIL::STATUS_FAILED;
    /** @deprecated */
    const STATUS_BOUNCED = EMAIL::STATUS_BOUNCED;
    /** @deprecated */
    const STATUS_OPENED = EMAIL::STATUS_OPENED;
    /** @deprecated */
    const STATUS_REJECTED = EMAIL::STATUS_REJECTED;
    /** @deprecated */
    const STATUS_DELAYED = EMAIL::STATUS_DELAYED;
    /** @deprecated */
    const STATUS_SOFT_BOUNCED = EMAIL::STATUS_SOFT_BOUNCED;
    /** @deprecated */
    const STATUS_MARKED_AS_SPAM = EMAIL::STATUS_MARKED_AS_SPAM;
    /** @deprecated */
    const STATUS_CLICKED = EMAIL::STATUS_CLICKED;

    // --------------------------------------------------------------------------

    /** @var \stdClass */
    public $from;

    /** @var Email */
    protected $oEmailModel;

    /**@var PHPMailer\PHPMailer */
    protected $oPhpMailer;

    /** @var array Type[] */
    protected $aEmailType = [];

    /** @var Override[] */
    protected $aEmailOverrides = [];

    /** @var array */
    protected $aTrackLinkCache = [];

    /** @var int */
    protected $iGenerateTrackingEmailId;

    /** @var string */
    protected $sGenerateTrackingEmailRef;

    /** @var mixed */
    protected $aGenerateTrackingNeedsVerified;

    /** @var string */
    protected $sDomain;

    /** @var \stdClass */
    protected $oLastEmail;

    // --------------------------------------------------------------------------

    /**
     * Emailer constructor.
     *
     * @throws FactoryException
     * @throws ModelException
     * @throws EmailerException
     */
    public function __construct()
    {
        $this->oEmailModel = Factory::model('Email', Constants::MODULE_SLUG);

        //  Set email related settings
        $this->from = (object) [
            'name'  => $this->getFromName(),
            'email' => $this->getFromEmail(),
        ];

        // --------------------------------------------------------------------------

        //  Load helpers
        Factory::helper('email');
        Factory::helper('string');

        // --------------------------------------------------------------------------

        // Auto-discover email types
        static::discoverTypes($this->aEmailType);

        // --------------------------------------------------------------------------

        //  Get Overrides
        $oOverrideModel        = Factory::model('TemplateOverride', Constants::MODULE_SLUG);
        $this->aEmailOverrides = $oOverrideModel->getAll();

        // --------------------------------------------------------------------------

        $this->oPhpMailer = new PHPMailer\PHPMailer();

        $this->oPhpMailer->isSMTP();

        $this->oPhpMailer->Host    = Config::get('EMAIL_HOST');
        $this->oPhpMailer->Port    = Config::get('EMAIL_PORT');
        $this->oPhpMailer->CharSet = PHPMailer\PHPMailer::CHARSET_UTF8;

        if (Config::get('EMAIL_HOST') === 'localhost' || Config::get('EMAIL_HOST') === '127.0.0.1') {
            $this->oPhpMailer->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];
        }

        if (!is_null(Config::get('EMAIL_USERNAME')) && !is_null(Config::get('EMAIL_PASSWORD'))) {
            $this->oPhpMailer->SMTPAuth = true;
            $this->oPhpMailer->Username = Config::get('EMAIL_USERNAME');
            $this->oPhpMailer->Password = Config::get('EMAIL_PASSWORD');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Auto-discover all emails supplied by modules and the app
     *
     * @param array $aArray The array to populate with discoveries
     */
    public static function discoverTypes(array &$aArray): void
    {
        foreach (Components::modules() as $oModule) {
            static::loadTypes(
                $oModule->path . $oModule->moduleName . '/config/email_types.php',
                $oModule,
                $aArray
            );
        }

        $oApp = Components::getApp();
        static::loadTypes(
            $oApp->path . 'application/config/email_types.php',
            $oApp,
            $aArray
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Loads email types located in a config file at $sPath
     *
     * @param string    $sPath      The path to load
     * @param Component $oComponent The component which supplied the email type
     * @param array     $aArray     The array to populate
     *
     * @return void
     */
    public static function loadTypes($sPath, Component $oComponent, array &$aArray): void
    {
        if (file_exists($sPath)) {
            include $sPath;
            if (!empty($config['email_types'])) {
                foreach ($config['email_types'] as $oType) {
                    static::addType($oType, $oComponent, $aArray);
                }
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the available email types in the system, sorted alphabetically by name
     *
     * @return Type[]
     */
    public function getTypes(): array
    {
        arraySortMulti($this->aEmailType, 'name');
        return $this->aEmailType;
    }

    // --------------------------------------------------------------------------

    /**
     * Return the email types as a flat array
     *
     * @return string[]
     */
    public function getTypesFlat(): array
    {
        $aTypes = $this->getTypes();
        return array_combine(
            arrayExtractProperty($aTypes, 'slug'),
            arrayExtractProperty($aTypes, 'name'),
        );
    }

    // --------------------------------------------------------------------------

    /**
     * Returns a specific type of email
     *
     * @param string $sType The type to look for
     *
     * @return Type|null
     */
    public function getType(string $sType): ?Type
    {
        return array_key_exists($sType, $this->aEmailType) ? $this->aEmailType[$sType] : null;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a new email type to the stack
     *
     * @param stdClass  $oData      An object representing the email type
     * @param Component $oComponent The component which supplied the email type
     * @param array     $aArray     The array to populate
     *
     * @return bool
     */
    public static function addType(stdClass $oData, Component $oComponent, array &$aArray): bool
    {
        if (!empty($oData->slug) && !empty($oData->template_body)) {

            $aArray[$oData->slug] = Factory::resource('Type', Constants::MODULE_SLUG, [
                'slug'            => $oData->slug,
                'name'            => $oData->name,
                'component'       => $oComponent,
                'description'     => $oData->description,
                'is_hidden'       => (bool) ($oData->is_hidden ?? false),
                'is_editable'     => (bool) ($oData->is_editable ?? true),
                'can_unsubscribe' => (bool) ($oData->can_unsubscribe ?? true),
                'template_header' => $oData->template_header ?? null,
                'template_body'   => $oData->template_body,
                'template_footer' => $oData->template_footer ?? null,
                'default_subject' => $oData->default_subject,
                'factory'         => $oData->factory ?? null,
            ]);

            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Send an email
     *
     * @param array|object $mInput    The email object
     * @param bool         $bGraceful Whether to gracefully fail or not
     * @param bool         $bSendNow  Whether to send now, or soon via cron
     *
     * @return stdClass|null
     * @throws EmailerException
     * @throws FactoryException
     * @throws ModelException
     * @throws PHPMailer\Exception
     */
    public function send($mInput, bool $bGraceful = false, bool $bSendNow = true): ?stdClass
    {
        //  We got something to work with?
        if (empty($mInput)) {
            return $this->sendError('No Input.', $bGraceful);
        }

        // --------------------------------------------------------------------------

        //  Ensure $mInput is an object
        $oInput = !is_object($mInput)
            ? (object) $mInput
            : $mInput;

        // --------------------------------------------------------------------------

        //  Check we have at least a user_id/email and an email type
        if ((empty($oInput->to_id) && empty($oInput->to_email)) || empty($oInput->type)) {
            return $this->sendError('Missing user ID, user email or email type.', $bGraceful);
        }

        //  If no email has been given make sure it's null
        if (empty($oInput->to_email)) {
            $oInput->to_email = null;
        }

        //  If no id has been given make sure it's null
        if (empty($oInput->to_id)) {
            $oInput->to_id = null;
        }

        //  If no internal_ref has been given make sure it's null
        if (empty($oInput->internal_ref)) {
            $oInput->internal_ref = null;
        }

        //  Make sure that at least empty data is available
        if (empty($oInput->data)) {
            $oInput->data = new stdClass();
        }

        // --------------------------------------------------------------------------

        //  Lookup the email type
        if (empty($this->aEmailType[$oInput->type])) {
            return $this->sendError('"' . $oInput->type . '" is not a valid email type.', $bGraceful);
        }

        // --------------------------------------------------------------------------

        //  If we're sending to an email address, try and associate it to a registered user
        try {

            /** @var \Nails\Auth\Model\User $oUserModel */
            $oUserModel = Factory::model('User', Auth\Constants::MODULE_SLUG);

            if ($oInput->to_email) {

                /** @var \Nails\Auth\Resource\User $oUser */
                $oUser         = $oUserModel->getByEmail($oInput->to_email);
                $oInput->to_id = $oUser->id ?? null;

            } elseif ($oInput->to_id) {

                //  Sending to an ID, fetch the user's email
                /** @var \Nails\Auth\Resource\User $oUser */
                $oUser            = $oUserModel->getById($oInput->to_id);
                $oInput->to_email = $oUser->email ?? null;
            }

        } catch (FactoryException $e) {
            //  If this goes wrong, don't worry about it
        }

        // --------------------------------------------------------------------------

        /**
         * Generate a unique reference - ref is sent in each email and can allow the
         * system to generate 'view online' links
         */

        $oInput->ref = $this->generateReference();

        // --------------------------------------------------------------------------

        /**
         * Double check we have an email address (a user may exist but not have an
         * email address set)
         */

        if (empty($oInput->to_email)) {
            return $this->sendError('No email address to send to.', $bGraceful);
        }

        // --------------------------------------------------------------------------

        //  Add to the archive table
        /** @var \DateTime $oNow */
        $oNow       = Factory::factory('DateTime');
        $oInput->id = $this->oEmailModel->create([
            'ref'            => $oInput->ref,
            'status'         => $bSendNow ? $this->oEmailModel::STATUS_PENDING : $this->oEmailModel::STATUS_QUEUED,
            'user_id'        => $oInput->to_id,
            'user_email'     => $oInput->to_email,
            'type'           => $oInput->type,
            'email_vars'     => json_encode($oInput->data),
            'internal_ref'   => $oInput->internal_ref,
            'queued'         => $oNow->format('Y-m-d H:i:s'),
            'queue_priority' => $oInput->queue_priority ?? \Nails\Email\Interfaces\Email::QUEUE_PRIORITY_NORMAL,
        ]);

        if (empty($oInput->id)) {
            return $this->sendError('Failed to create the email record.', $bGraceful);

        } elseif ($bSendNow) {

            if ($this->doSend($oInput->id)) {
                return (object) [
                    'id'  => $oInput->id,
                    'ref' => $oInput->ref,
                ];

            } else {
                return $this->sendError($this->lastError(), $bGraceful);
            }

        } else {
            return (object) [
                'id'  => $oInput->id,
                'ref' => $oInput->ref,
            ];
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sends an email again
     *
     * @param mixed $mEmailIdRef The email's ID or ref
     *
     * @return bool
     * @throws EmailerException
     * @throws FactoryException
     * @throws PHPMailer\Exception
     * @todo This should probably create a new row
     */
    public function resend($mEmailIdRef)
    {
        if (is_numeric($mEmailIdRef)) {
            $oEmail = $this->getById($mEmailIdRef);
        } else {
            $oEmail = $this->getByRef($mEmailIdRef);
        }

        if (empty($oEmail)) {
            $this->setError('"' . $mEmailIdRef . '" is not a valid Email ID or reference.');
            return false;
        }

        return $this->doSend($oEmail);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the user has unsubscribed from this email type
     *
     * @param int    $iUserId The user ID to check for
     * @param string $sType   The type of email to check against
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     */
    public function userHasUnsubscribed(int $iUserId, string $sType): bool
    {
        $oModel = Factory::model('UserEmailBlocker', Auth\Constants::MODULE_SLUG);
        return (bool) $oModel->countAll([
            new Where('user_id', $iUserId),
            new Where('type', $sType),
        ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a user is suspended
     *
     * @param int $iUserId The user ID to check
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     */
    public function userIsSuspended(int $iUserId): bool
    {
        $oModel = Factory::model('User', Auth\Constants::MODULE_SLUG);
        return (bool) $oModel->countAll([
            new Where($oModel->getTableAlias() . '.id', $iUserId),
            new Where('is_suspended', true),
        ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Unsubscribe a user from a particular email type
     *
     * @param int    $iUserId The user ID to unsubscribe
     * @param string $sType   The type of email to unsubscribe from
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     */
    public function unsubscribeUser(int $iUserId, string $sType): bool
    {
        if ($this->userHasUnsubscribed($iUserId, $sType)) {
            return true;
        }

        // --------------------------------------------------------------------------

        /** @var \DateTime $oNow */
        $oNow   = Factory::factory('DateTime');
        $oModel = Factory::model('UserEmailBlocker', Auth\Constants::MODULE_SLUG);
        return (bool) $oModel->create([
            'user_id' => $iUserId,
            'type'    => $sType,
            'created' => $oNow->format('Y-m-d H:i:s'),
        ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Subscribe a user to a particular email type
     *
     * @param int    $iUserId The user ID to subscribe
     * @param string $sType   The type of email to subscribe to
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     */
    public function subscribeUser(int $iUserId, string $sType): bool
    {
        if (!$this->userHasUnsubscribed($iUserId, $sType)) {
            return true;
        }

        $oModel = Factory::model('UserEmailBlocker', Auth\Constants::MODULE_SLUG);
        return $oModel->deleteWhere([
            ['user_id', $iUserId],
            ['type', $sType],
        ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Sends a template email immediately
     *
     * @param int|object $mEmailId The ID of the email to send, or the email object itself
     *
     * @return bool
     * @throws FactoryException
     * @throws ModelException
     * @throws EmailerException
     * @throws PHPMailer\Exception
     */
    public function doSend($mEmailId = false): bool
    {
        //  Get the email if $mEmailId is not an object
        if (is_numeric($mEmailId)) {

            $oEmail = $this->getById($mEmailId);
            if (!$oEmail) {
                $this->setError('Invalid email ID');
                return false;
            }

        } elseif (is_object($mEmailId)) {
            $oEmail = $mEmailId;

        } else {
            $this->setError('Invalid email ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $this->setEmailAsSending($oEmail);

        // --------------------------------------------------------------------------

        //  Check to see if the user has opted out of receiving these emails or is suspended
        if ($oEmail->to->id) {
            if ($this->userHasUnsubscribed($oEmail->to->id, $oEmail->type)) {
                $this->setEmailAsFailed($oEmail, 'Recipient has unsubscribed from this type of email');
                return true;

            } elseif ($this->userIsSuspended($oEmail->to->id)) {
                $this->setEmailAsFailed($oEmail, 'Recipient is suspended');
                return true;
            }
        }

        // --------------------------------------------------------------------------

        /**
         * Parse the body for <a> links and replace with a tracking URL
         * First clear out any previous link caches
         */

        $this->aTrackLinkCache = [];

        if ($oEmail->to->id && !$oEmail->to->email_verified) {
            $aVerify = [
                'id'   => $oEmail->to->id,
                'code' => $oEmail->to->email_verified_code,
            ];
        } else {
            $aVerify = null;
        }

        $oEmail->body->html = $this->parseLinks(
            $oEmail->body->html,
            $oEmail->id,
            $oEmail->ref,
            true,
            $aVerify
        );

        $oEmail->body->text = $this->parseLinks(
            $oEmail->body->text,
            $oEmail->id,
            $oEmail->ref,
            false,
            $aVerify
        );

        // --------------------------------------------------------------------------

        //  Handle routing of email on non-production environments
        if (Environment::not(Environment::ENV_PROD)) {

            if (Config::get('EMAIL_OVERRIDE')) {
                $oEmail->to->email = Config::get('EMAIL_OVERRIDE');

            } elseif (!empty(Config::get('EMAIL_WHITELIST'))) {

                $aWhitelist = array_values(
                    array_filter(
                        (array) Config::get('EMAIL_WHITELIST')
                    )
                );

                if (!in_array($oEmail->to->email, $aWhitelist)) {

                    $bMatch = false;
                    foreach ($aWhitelist as $sRule) {
                        if (preg_match('/' . $sRule . '/', $oEmail->to->email)) {
                            $bMatch = true;
                            break;
                        }
                    }

                    if (!$bMatch) {
                        //  No matches so silently fail
                        $this->setEmailAsFailed($oEmail, 'Recipient is not whitelisted');
                        return true;
                    }
                }

            } elseif (Config::get('APP_DEVELOPER_EMAIL')) {
                $oEmail->to->email = Config::get('APP_DEVELOPER_EMAIL');

            } else {

                $sError = 'Non-production environment detected and neither EMAIL_OVERRIDE, EMAIL_WHITELIST nor APP_DEVELOPER_EMAIL is set';
                $this->setEmailAsFailed($oEmail, $sError);

                throw new EmailerException(
                    $sError
                );
            }
        }

        // --------------------------------------------------------------------------

        //  Start prepping the email
        $this->oPhpMailer->clearReplyTos();
        $this->oPhpMailer->clearAllRecipients();
        $this->oPhpMailer->clearAttachments();
        $this->oPhpMailer->clearCustomHeaders();

        $aReplyTos = preg_split('/[,;]/', $oEmail->from->email);
        foreach ($aReplyTos as $sReplyTo) {
            $this->oPhpMailer->addReplyTo($sReplyTo, $oEmail->from->name);
        }

        $this->oPhpMailer->setFrom($this->from->email, $oEmail->from->name);
        $this->oPhpMailer->addAddress($oEmail->to->email);
        $this->oPhpMailer->isHTML(true);

        //  If the email can be unsubscribed from, set the List-Unsubscribe header
        if (!empty($oEmail->data->url->unsubscribe)) {
            $this->oPhpMailer->addCustomHeader('List-Unsubscribe', sprintf(
                '<%s>',
                $oEmail->data->url->unsubscribe
            ));
            $this->oPhpMailer->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        }

        $this->oPhpMailer->Subject = $oEmail->subject;
        $this->oPhpMailer->Body    = $oEmail->body->html;
        $this->oPhpMailer->AltBody = $oEmail->body->text;

        // --------------------------------------------------------------------------

        //  Add any attachments
        if (!empty($oEmail->data->attachments)) {
            foreach ($oEmail->data->attachments as $file) {

                if (is_array($file)) {
                    $_file     = isset($file[0]) ? $file[0] : null;
                    $_filename = isset($file[1]) ? $file[1] : null;
                } else {
                    $_file     = $file;
                    $_filename = null;
                }

                //  In case custom names support is added
                if (!$this->addAttachment($_file, $_filename)) {

                    $sError = 'Failed to add attachment "' . $_file . '"';
                    $this->setEmailAsFailed($oEmail, $sError);
                    $this->setError($sError);
                    return false;
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Add any CC/BCC's
        if (!empty($oEmail->data->cc)) {
            $this->oPhpMailer->addCC($oEmail->data->cc);
        }

        if (!empty($oEmail->data->bcc)) {
            $this->oPhpMailer->addBCC($oEmail->data->bcc);
        }

        // --------------------------------------------------------------------------

        //  Send! Turn off error reporting, if it fails we should handle it gracefully
        $_previous_error_reporting = error_reporting();
        error_reporting(0);

        if ($this->oPhpMailer->send()) {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            //  Update the counter on the email address
            /** @var Database $oDb */
            $oDb    = Factory::service('Database');
            $oModel = Factory::model('UserEmail', Auth\Constants::MODULE_SLUG);

            $oDb->set('count_sends', 'count_sends + 1', false);
            $oDb->where('email', $oEmail->to->email);
            $oDb->update($oModel->getTableName());

            $this->setEmailAsSent($oEmail);

            //  Note the last email
            $this->oLastEmail = $oEmail;

            return true;

        } else {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            $sError = 'Email failed to send at SMTP time; ' . $this->oPhpMailer->ErrorInfo;
            $this->setEmailAsFailed($oEmail, $sError);
            $this->setError($sError);

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets an email as failed with optional reason for failure
     *
     * @param \Nails\Email\Resource\Email|\stdClass $oEmail
     * @param string|null                           $sFailReason
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     */
    public function setEmailAsFailed($oEmail, string $sFailReason = null): Emailer
    {
        $this->oEmailModel->update(
            $oEmail->id,
            [
                'status'      => $this->oEmailModel::STATUS_FAILED,
                'fail_reason' => $sFailReason,
            ]
        );

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets an email as sent with timestamp
     *
     * @param \Nails\Email\Resource\Email|\stdClass $oEmail
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     */
    public function setEmailAsSent($oEmail): Emailer
    {
        $this->oEmailModel->update(
            $oEmail->id,
            [
                'status' => $this->oEmailModel::STATUS_SENT,
                'sent'   => Factory::factory('DateTime')->format('Y-m-d H:i:s'),
            ]
        );

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Sets an email as sending
     *
     * @param \Nails\Email\Resource\Email|\stdClass $oEmail
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     */
    public function setEmailAsSending($oEmail): Emailer
    {
        $this->oEmailModel->update(
            $oEmail->id,
            [
                'status' => $this->oEmailModel::STATUS_SENDING,
            ]
        );

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the last email which was sent
     *
     * @return stdClass|null
     */
    public function getLastEmail(): ?\stdClass
    {
        return $this->oLastEmail;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns emails from the archive
     *
     * @param int|null $page    The page of results to retrieve
     * @param int|null $perPage The number of results per page
     * @param array    $data    Data to pass to getCountCommonEmail()
     *
     * @return \CI_DB_result
     * @throws FactoryException
     * @throws ModelException
     */
    public function getAllRawQuery(int $page = null, int $perPage = null, array $data = []): \CI_DB_result
    {
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        $oDb->select([
            $this->oEmailModel->getTableAlias() . '.id',
            $this->oEmailModel->getTableAlias() . '.ref',
            $this->oEmailModel->getTableAlias() . '.type',
            $this->oEmailModel->getTableAlias() . '.email_vars',
            $this->oEmailModel->getTableAlias() . '.user_email sent_to',
            'ue.is_verified email_verified',
            'ue.code email_verified_code',
            $this->oEmailModel->getTableAlias() . '.sent',
            $this->oEmailModel->getTableAlias() . '.status',
            $this->oEmailModel->getTableAlias() . '.fail_reason',
            $this->oEmailModel->getTableAlias() . '.read_count',
            $this->oEmailModel->getTableAlias() . '.link_click_count',
            'u.first_name',
            'u.last_name',
            'u.id user_id',
            'u.password user_password',
            'u.group_id user_group',
            'u.profile_img',
            'u.gender',
            'u.username',
        ]);

        //  Apply common items; pass $data
        $this->getCountCommonEmail($data);

        // --------------------------------------------------------------------------

        //  Facilitate pagination
        if (!is_null($page)) {

            /**
             * Adjust the page variable, reduce by one so that the offset is calculated
             * correctly. Make sure we don't go into negative numbers
             */

            $page--;
            $page = $page < 0 ? 0 : $page;

            //  Work out what the offset should be
            $perPage = is_null($perPage) ? 50 : (int) $perPage;
            $offset  = $page * $perPage;

            $oDb->limit($perPage, $offset);
        }

        return $oDb->get($this->oEmailModel->getTableName(true));
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all emails from the archive and formats them, optionally paginated
     *
     * @param int|null $iPage    The page number of the results, if null then no pagination
     * @param int|null $iPerPage How many items per page of paginated results
     * @param array    $aData    Any data to pass to getCountCommon()
     *
     * @return array
     * @throws FactoryException
     * @throws ModelException
     * @throws EmailerException
     * @throws NailsException
     */
    public function getAll(int $iPage = null, int $iPerPage = null, array $aData = []): array
    {
        $oResults   = $this->getAllRawQuery($iPage, $iPerPage, $aData);
        $aResults   = $oResults->result();
        $numResults = count($aResults);

        for ($i = 0; $i < $numResults; $i++) {
            $this->formatObject($aResults[$i]);
        }

        return $aResults;
    }

    // --------------------------------------------------------------------------

    /**
     * This method applies the conditionals which are common across the get_*()
     * methods and the count() method.
     *
     * @param array $data Data passed from the calling method
     *
     * @return void
     * @throws FactoryException
     * @throws ModelException
     **/
    protected function getCountCommonEmail(array $data = []): void
    {
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {
                $data['or_like'] = [];
            }

            $data['or_like'][] = [
                'column' => $this->oEmailModel->getTableAlias() . '.ref',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->oEmailModel->getTableAlias() . '.user_id',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->oEmailModel->getTableAlias() . '.user_email',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => 'ue.email',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => 'CONCAT(u.first_name, \' \', u.last_name)',
                'value'  => $data['keywords'],
            ];

            //  If the term looks like a formatted number, convert it to an int and search against the ID
            if (preg_match('/^(\d{1,3},?)+$/', $data['keywords'], $aMatches)) {
                $iKeywordAsId      = (int) preg_replace('/[^0-9]/', '', $aMatches[0]);
                $data['or_like'][] = [
                    'column' => 'u.id',
                    'value'  => $iKeywordAsId,
                ];
            }
        }

        if (!empty($data['type'])) {

            if (empty($data['where'])) {
                $data['where'] = [];
            }

            $data['where'][] = [
                'column' => $this->oEmailModel->getTableAlias() . '.type',
                'value'  => $data['type'],
            ];
        }

        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        /** @var Auth\Model\User $oUserModel */
        $oUserModel = Factory::model('User', Auth\Constants::MODULE_SLUG);
        /** @var Auth\Model\User\Email $oUserEmailModel */
        $oUserEmailModel = Factory::model('UserEmail', Auth\Constants::MODULE_SLUG);

        $oDb->join($oUserModel->getTableName() . ' u', 'u.id = ' . $this->oEmailModel->getTableAlias() . '.user_id', 'LEFT');
        $oDb->join($oUserEmailModel->getTableName() . ' ue', 'ue.email = ' . $this->oEmailModel->getTableAlias() . '.user_email', 'LEFT');

        $this->getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of records in the archive
     *
     * @param array $aData Data passed from the calling method
     *
     * @return int
     * @throws FactoryException
     * @throws ModelException
     */
    public function countAll(array $aData = []): int
    {
        $this->getCountCommonEmail($aData);

        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        return $oDb->count_all_results($this->oEmailModel->getTableName(true));
    }

    // --------------------------------------------------------------------------

    /**
     * Get en email from the archive by its ID
     *
     * @param int   $iId   The email's ID
     * @param array $aData The data array
     *
     * @return object|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getById(int $iId, array $aData = []): ?object
    {
        if (empty($aData['where'])) {
            $aData['where'] = [];
        }

        $aData['where'][] = [$this->oEmailModel->getTableAlias() . '.id', $iId];

        $aEmails = $this->getAll(null, null, $aData);

        return !empty($aEmails) ? reset($aEmails) : null;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an email from the archive by its reference
     *
     * @param string $sRef  The email's reference
     * @param array  $aData The data array
     *
     * @return object|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function getByRef(string $sRef, array $aData = []): ?object
    {
        if (empty($aData['where'])) {
            $aData['where'] = [];
        }

        $aData['where'][] = [$this->oEmailModel->getTableAlias() . '.ref', $sRef];

        $aEmails = $this->getAll(null, null, $aData);

        return !empty($aEmails) ? reset($aEmails) : null;
    }

    // --------------------------------------------------------------------------

    /**
     * Validates an email hash
     *
     * @param string $sRef  The email's ref
     * @param string $sGuid The email's guid
     * @param string $sHash The hash to validate
     *
     * @return bool
     */
    public function validateHash(string $sRef, string $sGuid, string $sHash): bool
    {
        return isAdmin() || $this->generateHash($sRef, $sGuid) === $sHash;
    }

    // --------------------------------------------------------------------------

    /**
     * Generates an email hash
     *
     * @param string $sRef  The email's ref
     * @param string $sGuid The email's guid
     *
     * @return string
     */
    public function generateHash(string $sRef, string $sGuid): string
    {
        return md5($sGuid . Config::get('PRIVATE_KEY') . $sRef);
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an attachment to an email
     *
     * @param string $sFilePath The file's path
     * @param string $sFileName The filename to give the attachment
     *
     * @return bool
     * @throws PHPMailer\Exception
     */
    protected function addAttachment($sFilePath, $sFileName = null)
    {
        if (!file_exists($sFilePath)) {
            return false;
        }

        return $this->oPhpMailer->addAttachment($sFilePath, $sFileName);
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a unique reference for an email, optionally exclude strings
     *
     * @param array $exclude Strings to exclude from the reference
     *
     * @return string
     * @throws FactoryException
     */
    protected function generateReference($exclude = [])
    {
        $oDb = Factory::service('Database');

        do {

            $bRefOk = false;

            do {

                $ref = strtoupper(random_string('alnum', 10));
                if (array_search($ref, $exclude) === false) {
                    $bRefOk = true;
                }

            } while (!$bRefOk);

            $oDb->where('ref', $ref);
            $result = $oDb->get($this->oEmailModel->getTableName());

        } while ($result->num_rows());

        return $ref;
    }
    // --------------------------------------------------------------------------

    /**
     * Increments an email's open count and adds a tracking note
     *
     * @param string $sRef The email's reference
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     */
    public function trackOpen(string $sRef): self
    {
        $oEmail = $this->oEmailModel->getByRef($sRef);

        if ($oEmail) {

            /** @var Database $oDb */
            $oDb = Factory::service('Database');
            /** @var Event $oEvent */
            $oEvent = Factory::service('Event');
            /** @var Email\Track\Open $oTrackModel */
            $oTrackModel = Factory::model('EmailTrackOpen', Constants::MODULE_SLUG);
            /** @var \DateTime $oNow */
            $oNow = Factory::factory('DateTime');

            $oDb
                ->set('read_count', 'read_count + 1', false)
                ->where('id', $oEmail->id)
                ->update($this->oEmailModel->getTableName());

            $oTrackModel->create([
                'email_id' => $oEmail->id,
                'user_id'  => activeUser('id') ?: null,
                'created'  => $oNow->format('Y-m-d H:i:s'),
            ]);

            $oEvent->trigger(
                Events::EMAIL_TRACK_OPEN,
                Events::getEventNamespace(),
                [$oEmail]
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Increments a link's open count and adds a tracking note
     *
     * @param string $sRef    The email's reference
     * @param int    $iLinkId The link's ID
     *
     * @return string|null
     * @throws FactoryException
     * @throws ModelException
     */
    public function trackLink(string $sRef, int $iLinkId): ?string
    {
        $oEmail = $this->oEmailModel->getByRef($sRef);

        if ($oEmail) {

            /** @var Event $oEvent */
            $oEvent = Factory::service('Event');
            /** @var Database $oDb */
            $oDb = Factory::service('Database');
            /** @var Email\Link $oLinkModel */
            $oLinkModel = Factory::model('EmailLink', Constants::MODULE_SLUG);
            /** @var Email\Track\Link $oTrackModel */
            $oTrackModel = Factory::model('EmailTrackLink', Constants::MODULE_SLUG);
            /** @var \DateTime $oNow */
            $oNow = Factory::factory('DateTime');

            /** @var \Nails\Email\Resource\Email\Link $oLink */
            $oLink = $oLinkModel->getById($iLinkId);

            if ($oLink && $oLink->email_id === $oEmail->id) {

                $oDb
                    ->set('link_click_count', 'link_click_count + 1', false)
                    ->where('id', $oEmail->id)
                    ->update($this->oEmailModel->getTableName());

                $oTrackModel->create([
                    'email_id' => $oEmail->id,
                    'link_id'  => $oLink->id,
                    'user_id'  => activeUser('id') ?: null,
                    'created'  => $oNow->format('Y-m-d H:i:s'),
                ]);

                $oEvent->trigger(
                    Events::EMAIL_TRACK_LINK,
                    Events::getEventNamespace(),
                    [$oLink]
                );
            }
        }

        return $oLink->url ?? null;
    }

    // --------------------------------------------------------------------------

    /**
     * Parses a string for <a> links and replaces them with a trackable URL
     *
     * @param string   $sBody     The string to parse
     * @param int      $iEmailId  The email's ID
     * @param string   $sEmailRef The email's reference
     * @param bool     $bIsHtml   Whether or not this is the HTML version of the email
     * @param string[] $aVerify   Whether or not this user needs verified (i.e route tracking links through the verifier), false if not required, array if required
     *
     * @return string
     */
    protected function parseLinks(string $sBody, int $iEmailId, string $sEmailRef, bool $bIsHtml = true, array $aVerify = null)
    {
        //    Set the class variables for the ID and ref (need those in the callbacks)
        $this->iGenerateTrackingEmailId       = $iEmailId;
        $this->sGenerateTrackingEmailRef      = $sEmailRef;
        $this->aGenerateTrackingNeedsVerified = $aVerify;

        // --------------------------------------------------------------------------

        $sBody = $bIsHtml
            ? preg_replace_callback(
                '/<a .*?(href="(https?:\/\/[^"]+)").*?>((.|\n)*?)<\/a>/',
                [$this, 'processLinkHtml'],
                $sBody
            )
            : preg_replace_callback(
                '/https?:\/\/[^ \n]+/',
                [$this, 'processLinkUrl'],
                $sBody
            );

        // --------------------------------------------------------------------------

        //  And null these again, so nothing gets confused
        $this->iGenerateTrackingEmailId       = null;
        $this->sGenerateTrackingEmailRef      = null;
        $this->aGenerateTrackingNeedsVerified = null;

        // --------------------------------------------------------------------------

        return $sBody;
    }

    // --------------------------------------------------------------------------

    /**
     * Processes a link found by parseLinks()
     *
     * @param array $aLink The link elements
     *
     * @return string
     * @throws FactoryException
     * @throws HostNotKnownException
     */
    protected function processLinkHtml(array $aLink): string
    {
        $sHtml  = !empty($aLink[0]) ? $aLink[0] : '';
        $sUrl   = !empty($aLink[2]) ? $aLink[2] : '';
        $sTitle = !empty($aLink[3]) ? $aLink[3] : '';

        $sTitle = strip_tags($sTitle);
        $sTitle = trim($sTitle);
        $sTitle = $sTitle ?: $sUrl;

        // --------------------------------------------------------------------------

        /**
         * Only process if there's at least the HTML tag and a detected URL
         * otherwise it's not worth it/possible to accurately replace the tag
         */

        if ($sHtml && $sUrl) {
            $sHtml = $this->processLinkGenerate($sHtml, $sUrl, $sTitle, true);
        }

        return $sHtml;
    }

    // --------------------------------------------------------------------------

    /**
     * Process the URL of a link found by processLinkHtml()
     *
     * @param array $aUrl The URL elements
     *
     * @return string
     * @throws FactoryException
     * @throws HostNotKnownException
     */
    protected function processLinkUrl(array $aUrl): string
    {
        $sHtml  = !empty($aUrl[0]) ? $aUrl[0] : '';
        $sUrl   = $sHtml;
        $sTitle = null;

        // --------------------------------------------------------------------------

        //  Only process if there's a URL to process
        if ($sHtml && $sUrl) {
            $sHtml = $this->processLinkGenerate($sHtml, $sUrl, $sTitle, false);
        }

        return $sHtml;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a tracking URL
     *
     * @param string $sHtml   The Link HTML
     * @param string $sUrl    The Link's URL
     * @param string $sTitle  The Link's Title
     * @param bool   $bIsHtml Whether this is HTML or not
     *
     * @return string
     * @throws HostNotKnownException
     * @throws FactoryException
     */
    protected function processLinkGenerate(string $sHtml, string $sUrl, ?string $sTitle, bool $bIsHtml): string
    {
        //  Ensure URLs have a domain
        if (preg_match('/^\//', $sUrl)) {
            $sUrl = $this->getDomain() . $sUrl;
        }

        /**
         * Generate a tracking URL for this link
         * Firstly, check this URL hasn't been processed already (for this email)
         */

        if (array_key_exists(md5($sUrl), $this->aTrackLinkCache)) {

            //  Replace the URL and return the new tag
            $sHtml = str_replace(
                $sUrl,
                $this->aTrackLinkCache[md5($sUrl)],
                $sHtml
            );

        } else {

            /**
             * New URL, needs processed. We take the URL and the Title, store it in the
             * database and generate the new tracking link (inc. hashes etc). We'll cache
             * this link so we don't have to process it again.
             *
             * If the email we're sending to hasn't been verified yet we should set the
             * actual URL as the return_to value of the email verifier, that means that
             * every link in this email behaves as a verifying email. Obviously we shouldn't
             * do this for the actual email verifier...
             */

            if ($this->aGenerateTrackingNeedsVerified) {

                //  Make sure we're not applying this to an activation URL
                if (!preg_match('#email/verify/[0-9]*?/(.*?)#', $sUrl)) {
                    $sUrlWithVerification = siteUrl(sprintf(
                        'email/verify/%s/%s?return_to=%s',
                        $this->aGenerateTrackingNeedsVerified['id'],
                        $this->aGenerateTrackingNeedsVerified['code'],
                        urlencode($sUrl)
                    ));
                }
            }

            /** @var \DateTime $oNow */
            $oNow       = Factory::factory('DateTime');
            $oLinkModel = Factory::model('EmailLink', Constants::MODULE_SLUG);

            $iLinkId = $oLinkModel->create([
                'email_id' => $this->iGenerateTrackingEmailId,
                'url'      => $sUrlWithVerification ?? $sUrl,
                'title'    => $sTitle,
                'created'  => $oNow->format('Y-m-d H:i:s'),
                'is_html'  => $bIsHtml,
            ]);

            if ($iLinkId) {

                $sTime        = (string) time();
                $sTrackingUrl = siteUrl(sprintf(
                    'email/tracker/link/%s/%s/%s/%s',
                    $this->sGenerateTrackingEmailRef,
                    $sTime,
                    $this->generateHash($this->sGenerateTrackingEmailRef, $sTime),
                    $iLinkId
                ));

                $this->aTrackLinkCache[md5($sUrl)] = $sTrackingUrl;

                // --------------------------------------------------------------------------

                /**
                 * Replace the URL and return the new tag. $sUrl in quotes so we only replace
                 * hyperlinks and not something else, such as an image's URL
                 */

                $sHtml = $bIsHtml
                    ? str_replace('"' . $sUrl . '"', $sTrackingUrl, $sHtml)
                    : str_replace($sUrl, $sTrackingUrl, $sHtml);
            }
        }

        return $sHtml;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the domain to use for the email
     *
     * @return string
     * @throws HostNotKnownException
     * @throws FactoryException
     */
    protected function getDomain()
    {
        if (!empty($this->sDomain)) {
            return $this->sDomain;

        } elseif (siteUrl() === '/') {
            /** @var Input $oInput */
            $oInput    = Factory::service('Input');
            $sHost     = $oInput->server('SERVER_NAME');
            $sProtocol = $oInput->server('REQUEST_SCHEME') ?: 'http';
            if (empty($sHost)) {
                throw new HostNotKnownException('Failed to resolve host; email links will be incomplete.');
            }

            $this->sDomain = $sProtocol . '://' . $sHost . '/';

        } else {
            $this->sDomain = siteUrl();
        }

        $this->sDomain = rtrim($this->sDomain, '/');

        return $this->sDomain;
    }

    // --------------------------------------------------------------------------

    /**
     * Format an email object
     *
     * @param object $oEmail The raw email object
     *
     * @throws FactoryException
     * @throws NailsException
     */
    protected function formatObject(&$oEmail)
    {
        $oEmail->type = !empty($this->aEmailType[$oEmail->type]) ? $this->aEmailType[$oEmail->type] : null;

        if (empty($oEmail->type)) {
            throw new NailsException(
                'Invalid Email Type: email with ID #' . $oEmail->id . ' has an invalid email type.'
            );
        }

        // --------------------------------------------------------------------------

        //  Some fields can be manipulated by the contents of email_vars
        $oEmail->data = json_decode($oEmail->email_vars) ?: new stdClass();
        unset($oEmail->email_vars);

        // --------------------------------------------------------------------------

        /**
         * If a subject is defined in the variables use that, if not check to see if one was
         * defined in the template; if not, fall back to a default subject
         */

        /**
         * Subject calculation is defined in the following order
         * 1. Admin override
         * 2. Data override
         * 3. Template Default
         * 4. Framework Default
         */

        $oOverride = getFromArray(
            (string) arraySearchMulti($oEmail->type->slug, 'slug', $this->aEmailOverrides),
            $this->aEmailOverrides
        );

        if (!empty($oOverride->subject)) {
            $oEmail->subject = $oOverride->subject;
        } elseif (!empty($oEmail->data->email_subject)) {
            $oEmail->subject = $oEmail->data->email_subject;
        } elseif (!empty($oEmail->type->default_subject)) {
            $oEmail->subject = $oEmail->type->default_subject;
        } else {
            $oEmail->subject = 'An E-mail from ' . Config::get('APP_NAME');
        }

        // --------------------------------------------------------------------------

        //  Template overrides
        if (!empty($oEmail->data->template_header)) {
            $oEmail->type->template_header = $oEmail->data->template_header;
        } elseif (empty($oEmail->data->template_header)) {
            $oEmail->type->template_header = 'email/structure/email_header';
        }

        if (!empty($oEmail->data->template_body)) {
            $oEmail->type->template_body = $oEmail->data->template_body;
        }

        if (!empty($oEmail->data->template_footer)) {
            $oEmail->type->template_footer = $oEmail->data->template_footer;
        } elseif (empty($oEmail->data->template_footer)) {
            $oEmail->type->template_footer = 'email/structure/email_footer';
        }

        // --------------------------------------------------------------------------

        //  Who the email is being sent to
        $oEmail->to                      = new stdClass();
        $oEmail->to->id                  = $oEmail->user_id;
        $oEmail->to->group_id            = $oEmail->user_group;
        $oEmail->to->email               = $oEmail->sent_to;
        $oEmail->to->username            = $oEmail->username;
        $oEmail->to->password            = $oEmail->user_password;
        $oEmail->to->first_name          = $oEmail->first_name;
        $oEmail->to->last_name           = $oEmail->last_name;
        $oEmail->to->profile_img         = $oEmail->profile_img;
        $oEmail->to->gender              = $oEmail->gender;
        $oEmail->to->gender              = $oEmail->gender;
        $oEmail->to->email_verified      = $oEmail->email_verified;
        $oEmail->to->email_verified_code = $oEmail->email_verified_code;

        unset($oEmail->user_id);
        unset($oEmail->sent_to);
        unset($oEmail->username);
        unset($oEmail->first_name);
        unset($oEmail->last_name);
        unset($oEmail->profile_img);
        unset($oEmail->gender);
        unset($oEmail->user_group);
        unset($oEmail->user_password);
        unset($oEmail->email_verified);
        unset($oEmail->email_verified_code);

        //  Who the email is being sent from
        $oEmail->from        = new stdClass();
        $oEmail->from->name  = !empty($oEmail->data->email_from_name) ? $oEmail->data->email_from_name : $this->from->name;
        $oEmail->from->email = !empty($oEmail->data->email_from_email) ? $oEmail->data->email_from_email : $this->from->email;

        //  Template details
        $oEmail->template               = new stdClass();
        $oEmail->template->header       = new stdClass();
        $oEmail->template->header->html = $oEmail->type->template_header;
        $oEmail->template->header->text = $oEmail->type->template_header . '_plaintext';
        $oEmail->template->body         = new stdClass();
        $oEmail->template->body->html   = $oEmail->type->template_body;
        $oEmail->template->body->text   = $oEmail->type->template_body . '_plaintext';
        $oEmail->template->footer       = new stdClass();
        $oEmail->template->footer->html = $oEmail->type->template_footer;
        $oEmail->template->footer->text = $oEmail->type->template_footer . '_plaintext';

        // --------------------------------------------------------------------------

        //  Add some extra, common variables for the template
        $oEmail->data->emailType = $oEmail->type;
        $oEmail->data->emailRef  = $oEmail->ref;
        $oEmail->data->sentFrom  = $oEmail->from;
        $oEmail->data->sentTo    = $oEmail->to;
        $oEmail->data->appName   = Config::get('APP_NAME');

        //  Common URLs
        $oEmail->data->url = new stdClass();

        //  View Online
        $sTime                         = (string) time();
        $sHash                         = $this->generateHash($oEmail->data->emailRef, $sTime);
        $oEmail->data->url->viewOnline = siteUrl(
            'email/view/' . $oEmail->data->emailRef . '/' . $sTime . '/' . $sHash
        );

        //  1-Click Unsubscribe
        if ($oEmail->type->can_unsubscribe && !empty($oEmail->to->id)) {

            /**
             * Bit of a hack; keep trying until there's no + symbol in the hash, try up to
             * 20 times before giving up @TODO: make this less hacky
             */

            $iCounter  = 0;
            $iAttempts = 20;

            /** @var Encrypt $oEncrypt */
            $oEncrypt = Factory::service('Encrypt');

            do {

                $sToken = $oEncrypt->encode(implode('|', [
                    $oEmail->type->slug,
                    $oEmail->data->emailRef,
                    $oEmail->to->id,
                ]));

                $iCounter++;

            } while ($iCounter <= $iAttempts && strpos($sToken, '+') !== false);

            $oEmail->data->url->unsubscribe = sprintf(
                siteUrl('email/unsubscribe?ref=%s&token=%s'),
                $oEmail->ref,
                $sToken
            );
        }

        /** @var Input $oInput */
        $oInput = Factory::service('Input');

        //  Tracker Image (not on view online links though)
        $oEmail->data->url->trackerImg = '';
        if (!$oInput->isCli() && !preg_match('/^email\/view\/[a-zA-Z0-9]+\/[0-9]+\/[a-zA-Z0-9]+$/', uri_string())) {
            $sTime                         = (string) time();
            $sHash                         = $this->generateHash($oEmail->data->emailRef, $sTime);
            $sImgSrc                       = siteUrl('email/tracker/' . $oEmail->data->emailRef . '/' . $sTime . '/' . $sHash) . '/0.gif';
            $oEmail->data->url->trackerImg = $sImgSrc;
        }

        // --------------------------------------------------------------------------

        //  Subject
        $oEmail->subject = $this->render($oEmail->subject, $oEmail->data);

        //  Add the rendered subject to the data array so the body can sue it
        $oEmail->data->email_subject = $oEmail->subject;

        //  Body
        $oEmail->body = new stdClass();

        //  HTML Version
        $oView = Factory::service('View');

        $oEmail->body->html = $oView->load(
            $oEmail->template->header->html,
            ['emailObject' => $oEmail],
            true
        );
        if (!empty($oOverride->body_html)) {

            $oEmail->body->html .= $oOverride->body_html;

        } else {
            $oEmail->body->html .= $oView->load(
                $oEmail->template->body->html,
                ['emailObject' => $oEmail],
                true
            );
        }
        $oEmail->body->html .= $oView->load(
            $oEmail->template->footer->html,
            ['emailObject' => $oEmail],
            true
        );

        //  Plain text version
        $oEmail->body->text = $oView->load(
            $oEmail->template->header->text,
            ['emailObject' => $oEmail],
            true
        );
        if (!empty($oOverride->body_text)) {

            $oEmail->body->text .= $oOverride->body_text;

        } else {
            $oEmail->body->text .= $oView->load(
                $oEmail->template->body->text,
                ['emailObject' => $oEmail],
                true
            );
        }
        $oEmail->body->text .= $oView->load(
            $oEmail->template->footer->text,
            ['emailObject' => $oEmail],
            true
        );

        $oEmail->body->html = $this->render($oEmail->body->html, $oEmail->data);
        $oEmail->body->text = $this->render($oEmail->body->text, $oEmail->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Compiles a template, replacing functions and variable placeholders
     *
     * @param string       $sTemplate The template to use
     * @param object|array $mData     The data to use
     *
     * @return string
     */
    protected function render(string $sTemplate, $mData): string
    {
        try {

            /** @var Mustache $oMustache */
            $oMustache = Factory::service('Mustache');

            //  Any function which takes a single argument
            $sTemplate = preg_replace_callback(
                '/{{\s*([a-zA-Z0-9_]+)(\(([\'" ]*)?(.*?)([\'" ]*)?\))\s*}}/',
                function ($aMatches) {
                    $sFunction = getFromArray(1, $aMatches);
                    $sArgument = getFromArray(4, $aMatches);
                    return function_exists($sFunction)
                        ? call_user_func($sFunction, $sArgument)
                        : $aMatches[0];
                },
                $sTemplate
            );

            return $oMustache->render($sTemplate, $mData);

        } catch (\Exception $e) {
            return sprintf(
                'Render Failure: %s',
                $e->getMessage()
            );

        } catch (\Error $e) {
            return sprintf(
                'Render Failure: %s',
                $e->getMessage()
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns protected property $table
     *
     * @return string
     * @throws ModelException
     */
    public function getTableName(): string
    {
        return $this->oEmailModel->getTableName();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns protected property $tableAlias
     *
     * @return string
     * @throws ModelException
     * @deprecated
     */
    public function getTableAlias(): string
    {
        return $this->oEmailModel->getTableAlias();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the name of the table used for storing email blocks
     *
     * @return string
     * @throws FactoryException
     * @throws ModelException
     * @deprecated
     */
    public function getEmailBlockerTableName(): string
    {
        $oModel = Factory::model('UserEmailBlocker', Auth\Constants::MODULE_SLUG);
        return $oModel->getTableName();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the defined sending from name, or falls back to Config::get('APP_NAME')
     *
     * @return string|null
     * @throws FactoryException
     */
    public function getFromName(): ?string
    {
        $sFromName = appSetting('from_name', Constants::MODULE_SLUG) ?: Config::get('APP_NAME');
        return $sFromName ?: null;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the defined sending from email, or falls back to nobody@host
     *
     * @return string|null
     * @throws FactoryException
     * @throws EmailerException
     */
    public function getFromEmail(): ?string
    {
        $sFrom = appSetting('from_email', Constants::MODULE_SLUG) ?: null;
        if ($sFrom) {
            return $sFrom;
        }

        $sDomain    = parse_url(Config::get('BASE_URL'), PHP_URL_HOST);
        $aValidEnvs = [Environment::ENV_DEV, Environment::ENV_TEST, Environment::ENV_HTTP_TEST];

        if ($sDomain === 'localhost' && Environment::is($aValidEnvs)) {
            $sDomain = 'example.com';

        } elseif (!PHPMailer\PHPMailer::validateAddress('nobody@' . $sDomain)) {
            throw new EmailerException('nobody@' . $sDomain . ' is not a valid from email');
        }
        return 'nobody@' . $sDomain;
    }

    // --------------------------------------------------------------------------

    /**
     * Handles send errors, taking into account the graceful behaviour
     *
     * @param string $sError    The error
     * @param bool   $bGraceful Whether to be graceful or not
     *
     * @return null
     * @throws EmailerException
     */
    protected function sendError(string $sError, bool $bGraceful)
    {
        if (!$bGraceful) {
            throw new EmailerException($sError);
        }

        $this->setError($sError);
        return null;
    }
}
