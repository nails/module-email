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

use Mustache_Engine;
use Nails\Auth;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Service\Database;
use Nails\Common\Service\Input;
use Nails\Common\Traits\ErrorHandling;
use Nails\Common\Traits\GetCountCommon;
use Nails\Components;
use Nails\Config;
use Nails\Email\Constants;
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

    const STATUS_PENDING        = 'PENDING';
    const STATUS_SENT           = 'SENT';
    const STATUS_BOUNCED        = 'BOUNCED';
    const STATUS_OPENED         = 'OPENED';
    const STATUS_REJECTED       = 'REJECTED';
    const STATUS_DELAYED        = 'DELAYED';
    const STATUS_SOFT_BOUNCED   = 'SOFT_BOUNCED';
    const STATUS_MARKED_AS_SPAM = 'MARKED_AS_SPAM';
    const STATUS_CLICKED        = 'CLICKED';
    const STATUS_FAILED         = 'FAILED';

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
    protected $mGenerateTrackingNeedsVerified;

    /** @var string */
    protected $sDomain;

    /** @var \stdClass */
    protected $oLastEmail;

    // --------------------------------------------------------------------------

    /**
     * Emailer constructor.
     *
     * @throws FactoryException
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
        $aLocations = [
            Config::get('NAILS_COMMON_PATH') . 'config/email_types.php',
        ];

        foreach (Components::modules() as $oModule) {
            $aLocations[] = $oModule->path . $oModule->moduleName . '/config/email_types.php';
        }

        $aLocations[] = Config::get('NAILS_APP_PATH') . 'application/config/email_types.php';

        foreach ($aLocations as $sPath) {
            static::loadTypes($sPath, $aArray);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads email types located in a config file at $sPath
     *
     * @param string $sPath  The path to load
     * @param array  $aArray The array to populate
     *
     * @return void
     */
    public static function loadTypes($sPath, array &$aArray): void
    {
        if (file_exists($sPath)) {
            include $sPath;
            if (!empty($config['email_types'])) {
                foreach ($config['email_types'] as $oType) {
                    static::addType($oType, $aArray);
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
     * @param stdClass $oData  An object representing the email type
     * @param array    $aArray The array to populate
     *
     * @return bool
     */
    protected static function addType(stdClass $oData, array &$aArray): bool
    {
        if (!empty($oData->slug) && !empty($oData->template_body)) {

            $aArray[$oData->slug] = Factory::resource('Type', Constants::MODULE_SLUG, [
                'slug'            => $oData->slug,
                'name'            => $oData->name,
                'description'     => $oData->description,
                'is_hidden'       => property_exists($oData, 'is_hidden') ? (bool) $oData->is_hidden : false,
                'can_unsubscribe' => property_exists($oData, 'can_unsubscribe') ? (bool) $oData->can_unsubscribe : true,
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
     * @param object $input    The email object
     * @param bool   $graceful Whether to gracefully fail or not
     *
     * @return bool|stdClass
     * @throws EmailerException
     * @throws FactoryException
     * @throws ModelException
     * @throws PHPMailer\Exception
     */
    public function send($input, $graceful = false)
    {
        //  We got something to work with?
        if (empty($input)) {
            if (!$graceful) {
                throw new EmailerException('No Input');
            } else {
                $this->setError('EMAILER: No input');
            }
            return false;
        }

        // --------------------------------------------------------------------------

        //  Ensure $input is an object
        if (!is_object($input)) {
            $input = (object) $input;
        }

        // --------------------------------------------------------------------------

        //  Check we have at least a user_id/email and an email type
        if ((empty($input->to_id) && empty($input->to_email)) || empty($input->type)) {
            if (!$graceful) {
                throw new EmailerException('Missing user ID, user email or email type');
            } else {
                $this->setError('EMAILER: Missing user ID, user email or email type');
            }
            return false;
        }

        //  If no email has been given make sure it's null
        if (empty($input->to_email)) {
            $input->to_email = null;
        }

        //  If no id has been given make sure it's null
        if (empty($input->to_id)) {
            $input->to_id = null;
        }

        //  If no internal_ref has been given make sure it's null
        if (empty($input->internal_ref)) {
            $input->internal_ref = null;
        }

        //  Make sure that at least empty data is available
        if (empty($input->data)) {
            $input->data = new stdClass();
        }

        // --------------------------------------------------------------------------

        //  Lookup the email type
        if (empty($this->aEmailType[$input->type])) {

            if (!$graceful) {
                throw new EmailerException('"' . $input->type . '" is not a valid email type', 1);
            } else {
                $this->setError('EMAILER: Invalid Email Type "' . $input->type . '"');
            }

            return false;
        }

        // --------------------------------------------------------------------------

        //  If we're sending to an email address, try and associate it to a registered user
        try {
            $oUserModel = Factory::model('User', Auth\Constants::MODULE_SLUG);
            if ($input->to_email) {
                $_user = $oUserModel->getByEmail($input->to_email);
                if ($_user) {
                    $input->to_id = $_user->id;
                }
            } else {
                //  Sending to an ID, fetch the user's email
                $_user = $oUserModel->getById($input->to_id);
                if (!empty($_user->email)) {
                    $input->to_email = $_user->email;
                }
            }
        } catch (FactoryException $e) {
            //  If this goes wrong, don't worry about it
        }

        // --------------------------------------------------------------------------

        /**
         * Generate a unique reference - ref is sent in each email and can allow the
         * system to generate 'view online' links
         */

        $input->ref = $this->generateReference();

        // --------------------------------------------------------------------------

        /**
         * Double check we have an email address (a user may exist but not have an
         * email address set)
         */

        if (empty($input->to_email)) {
            if (!$graceful) {
                throw new EmailerException('No email address to send to', 1);
            } else {
                $this->setError('EMAILER: No email address to send to.');
                false;
            }
        }

        // --------------------------------------------------------------------------

        //  Add to the archive table
        $input->id = $this->oEmailModel->create([
            'ref'          => $input->ref,
            'status'       => static::STATUS_PENDING,
            'user_id'      => $input->to_id,
            'user_email'   => $input->to_email,
            'type'         => $input->type,
            'email_vars'   => json_encode($input->data),
            'internal_ref' => $input->internal_ref,
        ]);

        if (empty($input->id)) {
            if (!$graceful) {
                throw new EmailerException('Failed to create the email record', 1);
            } else {
                $this->setError('EMAILER: Failed to create the email record.');
                false;
            }
        }

        if ($this->doSend($input->id, $graceful)) {

            return (object) [
                'id'  => $input->id,
                'ref' => $input->ref,
            ];

        } else {
            return false;
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
     * @param int    $iUSerId The user ID to check for
     * @param string $sType   The type of email to check against
     *
     * @return bool
     * @throws FactoryException
     */
    public function userHasUnsubscribed($iUSerId, $sType)
    {
        $oDb = Factory::service('Database');
        $oDb->where('user_id', $iUSerId);
        $oDb->where('type', $sType);
        return (bool) $oDb->count_all_results(Config::get('NAILS_DB_PREFIX') . 'user_email_blocker');
    }

    // --------------------------------------------------------------------------

    /**
     * Determiens whether a suer is suspended
     *
     * @param integer $iUserId The user ID to check
     *
     * @return bool
     * @throws FactoryException
     */
    public function userIsSuspended($iUserId)
    {
        $oDb = Factory::service('Database');
        $oDb->where('id', $iUserId);
        $oDb->where('is_suspended', true);
        return (bool) $oDb->count_all_results(Config::get('NAILS_DB_PREFIX') . 'user');
    }

    // --------------------------------------------------------------------------

    /**
     * Unsubscribe a user from a particular email type
     *
     * @param int    $user_id The user ID to unsubscribe
     * @param string $type    The type of email to unsubscribe from
     *
     * @return bool
     * @throws FactoryException
     */
    public function unsubscribeUser($user_id, $type)
    {
        if ($this->userHasUnsubscribed($user_id, $type)) {
            return true;
        }

        // --------------------------------------------------------------------------

        $oDb = Factory::service('Database');
        $oDb->set('user_id', $user_id);
        $oDb->set('type', $type);
        $oDb->set('created', 'NOW()', false);
        $oDb->insert(Config::get('NAILS_DB_PREFIX') . 'user_email_blocker');

        return (bool) $oDb->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Subscribe a user to a particular email type
     *
     * @param int    $user_id The user ID to subscribe
     * @param string $type    The type of email to subscribe to
     *
     * @return bool
     * @throws FactoryException
     */
    public function subscribeUser($user_id, $type)
    {
        if (!$this->userHasUnsubscribed($user_id, $type)) {
            return true;
        }

        // --------------------------------------------------------------------------

        $oDb = Factory::service('Database');
        $oDb->where('user_id', $user_id);
        $oDb->where('type', $type);
        $oDb->delete(Config::get('NAILS_DB_PREFIX') . 'user_email_blocker');

        return (bool) $oDb->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Sends a template email immediately
     *
     * @param int|bool $emailId  The ID of the email to send, or the email object itself
     * @param bool     $graceful Whether or not to fail gracefully
     *
     * @return bool
     * @throws EmailerException
     * @throws FactoryException
     * @throws PHPMailer\Exception
     */
    protected function doSend($emailId = false, $graceful = false)
    {
        //  Get the email if $emailId is not an object
        if (is_numeric($emailId)) {

            $oEmail = $this->getById($emailId);
            if (!$oEmail) {

                $this->setError('EMAILER: Invalid email ID');
                return false;
            }

        } elseif (is_object($emailId)) {
            $oEmail = $emailId;

        } else {
            $this->setError('EMAILER: Invalid email ID');
            return false;
        }

        // --------------------------------------------------------------------------

        //  Check to see if the user has opted out of receiving these emails or is suspended
        if ($oEmail->to->id) {
            if ($this->userHasUnsubscribed($oEmail->to->id, $oEmail->type)) {
                $this->setEmailAsFailed($oEmail, 'Recipient has unsubscribed from this type of email');
                return true;
            }
            if ($this->userIsSuspended($oEmail->to->id)) {
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
            $mNeedsVerified = [
                'id'   => $oEmail->to->id,
                'code' => $oEmail->to->email_verified_code,
            ];
        } else {
            $mNeedsVerified = false;
        }

        $oEmail->body->html = $this->parseLinks(
            $oEmail->body->html,
            $oEmail->id,
            $oEmail->ref,
            true,
            $mNeedsVerified
        );

        $oEmail->body->text = $this->parseLinks(
            $oEmail->body->text,
            $oEmail->id,
            $oEmail->ref,
            false,
            $mNeedsVerified
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
                    'EMAILER: ' . $sError
                );
            }
        }

        // --------------------------------------------------------------------------

        //  Start prepping the email
        $this->oPhpMailer->clearReplyTos();
        $this->oPhpMailer->clearAllRecipients();
        $this->oPhpMailer->clearAttachments();
        $this->oPhpMailer->clearCustomHeaders();
        $this->oPhpMailer->setFrom($this->from->email, $oEmail->from->name);
        $this->oPhpMailer->addReplyTo($oEmail->from->email, $oEmail->from->name);
        $this->oPhpMailer->addAddress($oEmail->to->email);
        $this->oPhpMailer->isHTML(true);

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

                    if (!$graceful) {
                        throw new EmailerException($sError, 1);
                    } else {
                        $this->setError('EMAILER: ' . $sError);
                        return false;
                    }
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
            $oDb = Factory::service('Database');
            $oDb->set('count_sends', 'count_sends+1', false);
            $oDb->where('email', $oEmail->to->email);
            $oDb->update(Config::get('NAILS_DB_PREFIX') . 'user_email');

            $this->setEmailAsSent($oEmail);

            //  Note the last email
            $this->oLastEmail = $oEmail;

            return true;

        } else {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            $sError = 'Email failed to send at SMTP time; ' . $this->oPhpMailer->ErrorInfo;
            $this->setEmailAsFailed($oEmail, $sError);

            if (Environment::is(Environment::ENV_PROD)) {

                $this->setError($sError);

            } else {

                /**
                 * On non-production environments halt execution, this is an error with the configs
                 * and should probably be addressed
                 */

                if (!$graceful) {
                    throw new EmailerException($sError);
                } else {
                    $this->setError($sError);
                }
            }

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sets an email as failed with optional reason for failure
     *
     * @param \Nails\Email\Resource\Email|\stdClass $oEmail
     * @param string|null                 $sFailReason
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
                'status'      => static::STATUS_FAILED,
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
                'status' => static::STATUS_SENT,
                'sent'   => Factory::factory('DateTime')->format('Y-m-d H:i:s'),
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
     * @param integer $page    The page of results to retrieve
     * @param integer $perPage The number of results per page
     * @param array   $data    Data to pass to getCountCommonEmail()
     *
     * @return object
     * @throws FactoryException
     */
    public function getAllRawQuery($page = null, $perPage = null, $data = [])
    {
        $oDb = Factory::service('Database');
        $oDb->select([
            $this->getTableAlias() . '.id',
            $this->getTableAlias() . '.ref',
            $this->getTableAlias() . '.type',
            $this->getTableAlias() . '.email_vars',
            $this->getTableAlias() . '.user_email sent_to',
            'ue.is_verified email_verified',
            'ue.code email_verified_code',
            $this->getTableAlias() . '.sent',
            $this->getTableAlias() . '.status',
            $this->getTableAlias() . '.fail_reason',
            $this->getTableAlias() . '.read_count',
            $this->getTableAlias() . '.link_click_count',
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

        return $oDb->get($this->getTableName() . ' ' . $this->getTableAlias());
    }

    // --------------------------------------------------------------------------

    /**
     * Fetches all emails from the archive and formats them, optionally paginated
     *
     * @param int   $iPage    The page number of the results, if null then no pagination
     * @param int   $iPerPage How many items per page of paginated results
     * @param mixed $aData    Any data to pass to getCountCommon()
     *
     * @return array
     * @throws FactoryException
     */
    public function getAll($iPage = null, $iPerPage = null, $aData = [])
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
     **/
    protected function getCountCommonEmail($data = [])
    {
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {
                $data['or_like'] = [];
            }

            $data['or_like'][] = [
                'column' => $this->getTableAlias() . '.ref',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->getTableAlias() . '.user_id',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->getTableAlias() . '.user_email',
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
                'column' => $this->getTableAlias() . '.type',
                'value'  => $data['type'],
            ];
        }

        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        /** @var Auth\Model\User $oUserModel */
        $oUserModel = Factory::model('User', Auth\Constants::MODULE_SLUG);
        /** @var Auth\Model\User\Email $oUserEmailModel */
        $oUserEmailModel = Factory::model('UserEmail', Auth\Constants::MODULE_SLUG);

        $oDb->join($oUserModel->getTableName() . ' u', 'u.id = ' . $this->getTableAlias() . '.user_id', 'LEFT');
        $oDb->join($oUserEmailModel->getTableName() . ' ue', 'ue.email = ' . $this->getTableAlias() . '.user_email', 'LEFT');

        $this->getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of records in the archive
     *
     * @param array $data Data passed from the calling method
     *
     * @return mixed
     * @throws FactoryException
     */
    public function countAll($data)
    {
        $this->getCountCommonEmail($data);
        $oDb = Factory::service('Database');
        return $oDb->count_all_results($this->getTableName() . ' ' . $this->getTableAlias());
    }

    // --------------------------------------------------------------------------

    /**
     * Get en email from the archive by its ID
     *
     * @param int   $iId   The email's ID
     * @param array $aData The data array
     *
     * @return mixed   stdClass on success, false on failure
     * @throws FactoryException
     */
    public function getById($iId, $aData = [])
    {
        if (empty($aData['where'])) {
            $aData['where'] = [];
        }

        $aData['where'][] = [$this->getTableAlias() . '.id', $iId];

        $aEmails = $this->getAll(null, null, $aData);

        return !empty($aEmails) ? reset($aEmails) : false;
    }

    // --------------------------------------------------------------------------

    /**
     * Get an email from the archive by its reference
     *
     * @param string $sRef  The email's reference
     * @param array  $aData The data array
     *
     * @return mixed        stdClass on success, false on failure
     * @throws FactoryException
     */
    public function getByRef($sRef, $aData = [])
    {
        if (empty($aData['where'])) {
            $aData['where'] = [];
        }

        $aData['where'][] = [$this->getTableAlias() . '.ref', $sRef];

        $aEmails = $this->getAll(null, null, $aData);

        return !empty($aEmails) ? reset($aEmails) : false;
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
    public function validateHash($sRef, $sGuid, $sHash)
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
    public function generateHash($sRef, $sGuid)
    {
        return md5($sGuid . Config::get('APP_PRIVATE_KEY') . $sRef);
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
            $result = $oDb->get($this->getTableName());

        } while ($result->num_rows());

        return $ref;
    }
    // --------------------------------------------------------------------------

    /**
     * Increments an email's open count and adds a tracking note
     *
     * @param string $ref The email's reference
     *
     * @return void
     * @throws FactoryException
     */
    public function trackOpen($ref)
    {
        $oEmail = $this->getByRef($ref);
        if ($oEmail) {

            //  Update the read count and a add a track data point
            $oDb = Factory::service('Database');
            $oDb->set('read_count', 'read_count+1', false);
            $oDb->where('id', $oEmail->id);
            $oDb->update($this->getTableName());

            $oDb->set('created', 'NOW()', false);
            $oDb->set('email_id', $oEmail->id);

            if (activeUser('id')) {
                $oDb->set('user_id', activeUser('id'));
            }

            $oDb->insert(Config::get('NAILS_DB_PREFIX') . 'email_archive_track_open');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Increments a link's open count and adds a tracking note
     *
     * @param string  $sRef    The email's reference
     * @param integer $iLinkId The link's ID
     *
     * @return string
     * @throws FactoryException
     */
    public function trackLink($sRef, $iLinkId)
    {
        $oEmail = $this->getByRef($sRef);

        if ($oEmail) {

            //  Get the link which was clicked
            /** @var Database $oDb */
            $oDb = Factory::service('Database');
            $oDb->select('id, url');
            $oDb->where('email_id', $oEmail->id);
            $oDb->where('id', $iLinkId);
            $oLink = $oDb->get(Config::get('NAILS_DB_PREFIX') . 'email_archive_link')->row();

            if ($oLink) {

                //  Update the read count and a add a track data point
                $oDb->set('link_click_count', 'link_click_count+1', false);
                $oDb->where('id', $oEmail->id);
                $oDb->update($this->getTableName());

                //  Add a link trackback
                $oDb->set('created', 'NOW()', false);
                $oDb->set('email_id', $oEmail->id);
                $oDb->set('link_id', $oLink->id);

                if (activeUser('id')) {
                    $oDb->set('user_id', activeUser('id'));
                }

                $oDb->insert(Config::get('NAILS_DB_PREFIX') . 'email_archive_track_link');

                //  Return the URL to go to
                return $oLink->url;

            } else {
                return false;
            }
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Parses a string for <a> links and replaces them with a trackable URL
     *
     * @param string $body            The string to parse
     * @param int    $emailId         The email's ID
     * @param string $emailRef        The email's reference
     * @param bool   $isHtml          Whether or not this is the HTML version of the email
     * @param bool   $needsVerified   Whether or not this user needs verified (i.e route tracking links through the
     *                                verifier)
     *
     * @return string
     */
    protected function parseLinks($body, $emailId, $emailRef, $isHtml = true, $needsVerified = false)
    {
        //    Set the class variables for the ID and ref (need those in the callbacks)
        $this->iGenerateTrackingEmailId       = $emailId;
        $this->sGenerateTrackingEmailRef      = $emailRef;
        $this->mGenerateTrackingNeedsVerified = $needsVerified;

        // --------------------------------------------------------------------------

        if ($isHtml) {
            $pattern = '/<a .*?(href="(https?.*?|\/.*?)").*?>(.*?)<\/a>/';
            $body    = preg_replace_callback($pattern, [$this, 'processLinkHtml'], $body);
        } else {
            $pattern = '/(https?:\/\/|\/)([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?/';
            $body    = preg_replace_callback($pattern, [$this, 'processLinkUrl'], $body);
        }

        // --------------------------------------------------------------------------

        //    And null these again, so nothing gets confused
        $this->iGenerateTrackingEmailId       = null;
        $this->sGenerateTrackingEmailRef      = null;
        $this->mGenerateTrackingNeedsVerified = null;

        // --------------------------------------------------------------------------

        return $body;
    }

    // --------------------------------------------------------------------------

    /**
     * Processes a link found by _parse_links()
     *
     * @param array $link The link elements
     *
     * @return mixed|string
     * @throws FactoryException
     * @throws HostNotKnownException
     */
    protected function processLinkHtml($link)
    {
        $_html  = !empty($link[0]) ? $link[0] : '';
        $_url   = !empty($link[2]) ? $link[2] : '';
        $_title = isset($link[3]) && strip_tags($link[3]) ? strip_tags($link[3]) : $_url;

        // --------------------------------------------------------------------------

        /**
         * Only process if there's at least the HTML tag and a detected URL
         * otherwise it's not worth it/possible to accurately replace the tag
         */

        if ($_html && $_url) {
            $_html = $this->processLinkGenerate($_html, $_url, $_title, true);
        }

        return $_html;
    }

    // --------------------------------------------------------------------------

    /**
     * Process the URL of a link found by processLinkHtml()
     *
     * @param array $url The URL elements
     *
     * @return string
     * @return mixed|string
     * @throws FactoryException
     * @throws HostNotKnownException
     */
    protected function processLinkUrl($url)
    {
        $_html  = !empty($url[0]) ? $url[0] : '';
        $_url   = $_html;
        $_title = $_html;

        // --------------------------------------------------------------------------

        //  Only process if there's a URL to process
        if ($_html && $_url) {
            $_html = $this->processLinkGenerate($_html, $_url, $_title, false);
        }

        return $_html;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a tracking URL
     *
     * @param string $html    The Link HTML
     * @param string $url     The Link's URL
     * @param string $title   The Link's Title
     * @param bool   $is_html Whether this is HTML or not
     *
     * @return string
     * @throws HostNotKnownException
     * @throws FactoryException
     */
    protected function processLinkGenerate($html, $url, $title, $is_html)
    {
        //  Ensure URLs have a domain
        if (preg_match('/^\//', $url)) {
            $url = $this->getDomain() . $url;
        }

        /**
         * Generate a tracking URL for this link
         * Firstly, check this URL hasn't been processed already (for this email)
         */

        if (isset($this->aTrackLinkCache[md5($url)])) {

            $trackingUrl = $this->aTrackLinkCache[md5($url)];

            //  Replace the URL and return the new tag
            $html = str_replace($url, $trackingUrl, $html);

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

            if ($this->mGenerateTrackingNeedsVerified) {

                //  Make sure we're not applying this to an activation URL
                if (!preg_match('#email/verify/[0-9]*?/(.*?)#', $url)) {

                    $_user_id = $this->mGenerateTrackingNeedsVerified['id'];
                    $_code    = $this->mGenerateTrackingNeedsVerified['code'];
                    $_return  = urlencode($url);

                    $_url = siteUrl('email/verify/' . $_user_id . '/' . $_code . '?return_to=' . $_return);

                } else {
                    $_url = $url;
                }

            } else {
                $_url = $url;
            }

            $oDb = Factory::service('Database');
            $oDb->set('email_id', $this->iGenerateTrackingEmailId);
            $oDb->set('url', $_url);
            $oDb->set('title', $title);
            $oDb->set('created', 'NOW()', false);
            $oDb->set('is_html', $is_html);
            $oDb->insert(Config::get('NAILS_DB_PREFIX') . 'email_archive_link');

            $_id = $oDb->insert_id();

            if ($_id) {

                $_time       = time();
                $trackingUrl = 'email/tracker/link/' . $this->sGenerateTrackingEmailRef . '/' . $_time . '/';
                $trackingUrl .= $this->generateHash($this->sGenerateTrackingEmailRef, $_time) . '/' . $_id;
                $trackingUrl = siteUrl($trackingUrl);

                $this->aTrackLinkCache[md5($url)] = $trackingUrl;

                // --------------------------------------------------------------------------

                /**
                 * Replace the URL and return the new tag. $url in quotes so we only replace
                 * hyperlinks and not something else, such as an image's URL
                 */

                $html = str_replace('"' . $url . '"', $trackingUrl, $html);
            }
        }

        return $html;
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
            $oInput    = Factory::service('Input');
            $sHost     = $oInput->server('SERVER_NAME');
            $sProtocol = $oInput->server('REQUEST_SCHEME') ?: 'http';
            if (empty($sHost)) {
                throw new HostNotKnownException('Failed to resolve host; email links will be incomplete.');
            } else {
                $this->sDomain = $sProtocol . '://' . $sHost . '/';
            }
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
        $iTime                         = time();
        $sHash                         = $this->generateHash($oEmail->data->emailRef, $iTime);
        $oEmail->data->url->viewOnline = siteUrl(
            'email/view/' . $oEmail->data->emailRef . '/' . $iTime . '/' . $sHash
        );

        //  1-Click Unsubscribe
        $oEmail->data->url->unsubscribe = '';
        if ($oEmail->type->can_unsubscribe && !empty($oEmail->to->id)) {

            /**
             * Bit of a hack; keep trying until there's no + symbol in the hash, try up to
             * 20 times before giving up @TODO: make this less hacky
             */

            $iCounter  = 0;
            $iAttempts = 20;
            $oEncrypt  = Factory::service('Encrypt');

            do {

                $sToken = $oEncrypt->encode(
                    $oEmail->type->slug . '|' . $oEmail->data->emailRef . '|' . $oEmail->to->id,
                    Config::get('APP_PRIVATE_KEY')
                );
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
            $iTime                         = time();
            $sHash                         = $this->generateHash($oEmail->data->emailRef, $iTime);
            $sImgSrc                       = siteUrl('email/tracker/' . $oEmail->data->emailRef . '/' . $iTime . '/' . $sHash) . '/0.gif';
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
     * @param object|Array $mData     The data to use
     *
     * @return string
     * @throws FactoryException
     */
    protected function render(string $sTemplate, $mData): string
    {
        /** @var Mustache_Engine $oMustache */
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
    }

    // --------------------------------------------------------------------------

    /**
     * Returns protected property $table
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->oEmailModel->getTableName();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns protected property $tableAlias
     *
     * @return string
     */
    public function getTableAlias()
    {
        return $this->oEmailModel->getTableAlias();
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the defined sending from name, or falls back to Config::get('APP_NAME')
     *
     * @return string
     */
    public function getFromName()
    {
        return appSetting('from_name', Constants::MODULE_SLUG) ?: Config::get('APP_NAME');
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the defined sending from email, or falls back to nobody@host
     *
     * @return string
     * @throws EmailerException
     */
    public function getFromEmail()
    {
        $sFrom = appSetting('from_email', Constants::MODULE_SLUG);
        if ($sFrom) {
            return $sFrom;
        } else {
            $sDomain    = parse_url(Config::get('BASE_URL'), PHP_URL_HOST);
            $aValidEnvs = [Environment::ENV_DEV, Environment::ENV_TEST, Environment::ENV_HTTP_TEST];
            if ($sDomain === 'localhost' && Environment::is($aValidEnvs)) {
                $sDomain = 'example.com';
            } elseif (!PHPMailer\PHPMailer::validateAddress('nobody@' . $sDomain)) {
                throw new EmailerException('nobody@' . $sDomain . ' is not a valid from email');
            }
            return 'nobody@' . $sDomain;
        }
    }
}
