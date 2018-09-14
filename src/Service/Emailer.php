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

use Nails\Common\Traits\ErrorHandling;
use Nails\Common\Traits\GetCountCommon;
use Nails\Email\Exception\EmailerException;
use Nails\Email\Exception\HostNotKnownException;
use Nails\Environment;
use Nails\Factory;

class Emailer
{
    use ErrorHandling;
    use GetCountCommon;

    // --------------------------------------------------------------------------

    public $from;

    protected $oCi;
    protected $aEmailType;
    protected $aTrackLinkCache;
    protected $sTable;
    protected $sTableAlias;
    protected $bHasDeveloperMail;
    protected $_generate_tracking_email_id;
    protected $_generate_tracking_email_ref;
    protected $_generate_tracking_needs_verified;
    protected $sDomain;

    // --------------------------------------------------------------------------

    /**
     * Construct the library
     *
     * @param array $config An optional config array
     */
    public function __construct($config = [])
    {
        $this->oCi =& get_instance();

        // --------------------------------------------------------------------------

        //  Set email related settings
        $this->from        = new \stdClass();
        $this->from->name  = $this->getFromName();
        $this->from->email = $this->getFromEmail();

        // --------------------------------------------------------------------------

        //  Load Email library
        $this->oCi->load->library('email');

        // --------------------------------------------------------------------------

        //  Load helpers
        Factory::helper('email');
        Factory::helper('string');

        // --------------------------------------------------------------------------

        //  Set defaults
        $this->aEmailType      = [];
        $this->aTrackLinkCache = [];

        // --------------------------------------------------------------------------

        //  Define where we should look for email types
        $emailTypeLocations   = [];
        $emailTypeLocations[] = NAILS_COMMON_PATH . 'config/email_types.php';

        $modules = _NAILS_GET_MODULES();

        foreach ($modules as $module) {
            $emailTypeLocations[] = $module->path . $module->moduleName . '/config/email_types.php';
        }

        $emailTypeLocations[] = APPPATH . 'config/email_types.php';

        //  Find definitions
        foreach ($emailTypeLocations as $path) {
            $this->loadTypes($path);
        }

        // --------------------------------------------------------------------------

        $this->sTable      = NAILS_DB_PREFIX . 'email_archive';
        $this->sTableAlias = 'ea';

        // --------------------------------------------------------------------------

        $this->bHasDeveloperMail = defined('APP_DEVELOPER_EMAIL') && !empty(APP_DEVELOPER_EMAIL);

        // --------------------------------------------------------------------------

        if (site_url() === '/') {
            $oInput    = Factory::service('Input');
            $sHost     = $oInput->server('SERVER_NAME');
            $sProtocol = $oInput->server('REQUEST_SCHEME') ?: 'http';
            if (empty($sHost)) {
                throw new HostNotKnownException('Failed to resolve host; email links will be incomplete.');
            } else {
                $this->sDomain = $sProtocol . '://' . $sHost . '/';
            }
        } else {
            $this->sDomain = site_url();
        }

        $this->sDomain = rtrim($this->sDomain, '/');
    }

    // --------------------------------------------------------------------------

    /**
     * Loads email types located in a config file at $path
     *
     * @param  string $path The path to load
     *
     * @return void
     */
    protected function loadTypes($path)
    {
        if (file_exists($path)) {
            include $path;
            if (!empty($config['email_types'])) {
                foreach ($config['email_types'] as $type) {
                    $this->addType($type);
                }
            }
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the available email types in the system, sorted alphabetically by name
     * @return array
     */
    public function getTypes()
    {
        arraySortMulti($this->aEmailType, 'name');
        return $this->aEmailType;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds a new email type to the stack
     *
     * @param  \stdClass $data An object representing the email type
     *
     * @return boolean
     */
    protected function addType($data)
    {
        if (empty($data->slug) || empty($data->template_body)) {
            return false;
        }

        $temp                   = new \stdClass();
        $temp->slug             = $data->slug;
        $temp->name             = $data->name;
        $temp->description      = $data->description;
        $temp->isUnsubscribable = property_exists($data, 'isUnsubscribable') ? (bool) $data->isUnsubscribable : true;
        $temp->template_header  = empty($data->template_header) ? 'email/structure/header' : $data->template_header;
        $temp->template_body    = $data->template_body;
        $temp->template_footer  = empty($data->template_footer) ? 'email/structure/footer' : $data->template_footer;
        $temp->default_subject  = $data->default_subject;

        $this->aEmailType[$data->slug] = $temp;

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Send an email
     *
     * @param  object  $input    The email object
     * @param  boolean $graceful Whether to gracefully fail or not
     *
     * @throws EmailerException
     * @return boolean|\stdClass
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
            $input->data = new \stdClass();
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
        $oUserModel = Factory::model('User', 'nails/module-auth');
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

        // --------------------------------------------------------------------------

        //  Check to see if the user has opted out of receiving these emails
        if ($input->to_id) {
            if ($this->userHasUnsubscribed($input->to_id, $input->type)) {
                //  User doesn't want to receive these notifications; abort.
                return true;
            }
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
        $oDb = Factory::service('Database');
        $oDb->set('ref', $input->ref);
        $oDb->set('user_id', $input->to_id);
        $oDb->set('user_email', $input->to_email);
        $oDb->set('type', $input->type);
        $oDb->set('email_vars', json_encode($input->data));
        $oDb->set('internal_ref', $input->internal_ref);

        $oDb->insert($this->sTable);

        if ($oDb->affected_rows()) {
            $input->id = $oDb->insert_id();
        } else {
            if (!$graceful) {
                throw new EmailerException('Failed to create the email record', 1);
            } else {
                $this->setError('EMAILER: Failed to create the email record.');
                false;
            }
        }

        if ($this->doSend($input->id, $graceful)) {

            //  Mail sent, mark the time
            $oDb->set('sent', 'NOW()', false);
            $oDb->where('id', $input->id);
            $oDb->update($this->sTable);

            $oOut      = new \stdClass();
            $oOut->id  = $input->id;
            $oOut->ref = $input->ref;

            return $oOut;

        } else {

            //  Mail failed, update the status
            $oDb->set('status', 'FAILED');
            $oDb->where('id', $input->id);
            $oDb->update($this->sTable);

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Sends an email again
     * @todo This should probably create a new row
     *
     * @param  mixed $mEmailIdRef The email's ID or ref
     *
     * @return boolean
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
     * @param  int    $user_id The user ID to check for
     * @param  string $type    The type of email to check against
     *
     * @return boolean
     */
    public function userHasUnsubscribed($user_id, $type)
    {
        $oDb = Factory::service('Database');
        $oDb->where('user_id', $user_id);
        $oDb->where('type', $type);

        return (bool) $oDb->count_all_results(NAILS_DB_PREFIX . 'user_email_blocker');
    }

    // --------------------------------------------------------------------------

    /**
     * Unsubscribes a user from a particular email type
     *
     * @param  int    $user_id The user ID to unsubscribe
     * @param  string $type    The type of email to unsubscribe from
     *
     * @return boolean
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
        $oDb->insert(NAILS_DB_PREFIX . 'user_email_blocker');

        return (bool) $oDb->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Subscribe a user to a particular email type
     *
     * @param  int    $user_id The user ID to subscribe
     * @param  string $type    The type of email to subscribe to
     *
     * @return boolean
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
        $oDb->delete(NAILS_DB_PREFIX . 'user_email_blocker');

        return (bool) $oDb->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Sends a templated email immediately
     *
     * @param  int|bool $emailId  The ID of the email to send, or the email object itself
     * @param  boolean  $graceful Whether or not to fail gracefully
     *
     * @throws EmailerException
     * @return boolean
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

        /**
         * Parse the body for <a> links and replace with a tracking URL
         * First clear out any previous link caches
         */

        $this->aTrackLinkCache = [];

        if ($oEmail->to->id && !$oEmail->to->email_verified) {

            $bNeedsVerified = [
                'id'   => $oEmail->to->id,
                'code' => $oEmail->to->email_verified_code,
            ];

        } else {
            $bNeedsVerified = false;
        }

        $oEmail->body->html = $this->parseLinks(
            $oEmail->body->html,
            $oEmail->id,
            $oEmail->ref,
            true,
            $bNeedsVerified
        );
        $oEmail->body->text = $this->parseLinks(
            $oEmail->body->text,
            $oEmail->id,
            $oEmail->ref,
            false,
            $bNeedsVerified
        );

        // --------------------------------------------------------------------------

        //  If we're not on a production server, never send out to any live addresses
        if (Environment::not('PRODUCTION') || EMAIL_OVERRIDE) {
            if (EMAIL_OVERRIDE) {
                $oEmail->to->email = EMAIL_OVERRIDE;
            } elseif (APP_DEVELOPER_EMAIL) {
                $oEmail->to->email = APP_DEVELOPER_EMAIL;
            } else {
                //  Not sure where this is going; fall over *waaaa*
                throw new EmailerException(
                    'EMAILER: Non production environment and neither EMAIL_OVERRIDE nor APP_DEVELOPER_EMAIL is set'
                );
            }
        }

        // --------------------------------------------------------------------------

        //  Start prepping the email
        $this->oCi->email->clear(true);
        $this->oCi->email->from($this->from->email, $oEmail->from->name);
        $this->oCi->email->reply_to($oEmail->from->email, $oEmail->from->name);
        $this->oCi->email->to($oEmail->to->email);
        $this->oCi->email->subject($oEmail->subject);
        $this->oCi->email->message($oEmail->body->html);
        $this->oCi->email->set_alt_message($oEmail->body->text);

        // --------------------------------------------------------------------------

        //  Add any attachments
        if (!empty($oEmail->data->attachments)) {

            foreach ($oEmail->data->attachments as $file) {

                /**
                 * @todo: Support for when custom names can be set.
                 * It's in the CI 3.0 dev branch, wonder if it'll ever be
                 * released.
                 */

                if (is_array($file)) {
                    $_file     = isset($file[0]) ? $file[0] : null;
                    $_filename = isset($file[1]) ? $file[1] : null;
                } else {
                    $_file     = $file;
                    $_filename = null;
                }

                //  In case custom names support is added
                if (!$this->addAttachment($_file, $_filename)) {
                    if (!$graceful) {
                        throw new EmailerException('Failed to add attachment "' . $_file . '"', 1);
                    } else {
                        $this->setError('EMAILER: Insert Failed.');
                        return false;
                    }
                }
            }
        }

        // --------------------------------------------------------------------------

        //  Add any CC/BCC's
        if (!empty($oEmail->data->cc)) {
            $this->oCi->email->cc($oEmail->data->cc);
        }

        if (!empty($oEmail->data->bcc)) {
            $this->oCi->email->bcc($oEmail->data->bcc);
        }

        // --------------------------------------------------------------------------

        //  Send! Turn off error reporting, if it fails we should handle it gracefully
        $_previous_error_reporting = error_reporting();
        error_reporting(0);

        if ($this->oCi->email->send()) {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            //  Update the counter on the email address
            $oDb = Factory::service('Database');
            $oDb->set('count_sends', 'count_sends+1', false);
            $oDb->where('email', $oEmail->to->email);
            $oDb->update(NAILS_DB_PREFIX . 'user_email');

            return true;

        } else {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            // --------------------------------------------------------------------------

            //  Failed to send, notify developers
            $sSubject = 'Email #' . $oEmail->id . ' failed to send at SMTP time';
            $sMessage = 'Hi,' . "\n";
            $sMessage .= '' . "\n";
            $sMessage .= 'Email #' . $oEmail->id . ' failed to send at SMTP time' . "\n";
            $sMessage .= '' . "\n";
            $sMessage .= 'Please take a look as a matter of urgency; debugging data is below:' . "\n";
            $sMessage .= '' . "\n";
            $sMessage .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $sMessage .= '' . "\n";

            $sMessage .= $this->oCi->email->print_debugger();

            $sMessage .= '' . "\n";
            $sMessage .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $sMessage .= '' . "\n";
            $sMessage .= 'Additional debugging information:' . "\n";
            $sMessage .= '' . "\n";
            $sMessage .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $sMessage .= '' . "\n";
            $sMessage .= print_r($oEmail, true) . "\n";

            if (Environment::is('PRODUCTION')) {

                $this->setError('Email failed to send at SMTP time, developers informed');
                sendDeveloperMail($sSubject, $sMessage);

            } else {

                /**
                 * On non-production environments halt execution, this is an error with the configs
                 * and should probably be addressed
                 */

                if (!$graceful) {
                    throw new EmailerException('Email failed to send at SMTP time.', 1);
                } else {
                    $this->setError('Email failed to send at SMTP time.');
                }
            }

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Returns emails from the archive
     *
     * @param  integer $page    The page of results to retrieve
     * @param  integer $perPage The number of results per page
     * @param  array   $data    Data to pass to getCountCommonEmail()
     *
     * @return object
     */
    public function getAllRawQuery($page = null, $perPage = null, $data = [])
    {
        $oDb = Factory::service('Database');
        $oDb->select('ea.id,ea.ref,ea.type,ea.email_vars,ea.user_email sent_to,ue.is_verified email_verified');
        $oDb->select('ue.code email_verified_code,ea.sent,ea.status,ea.read_count,ea.link_click_count');
        $oDb->select('u.first_name,u.last_name,u.id user_id,u.password user_password,u.group_id user_group');
        $oDb->select('u.profile_img,u.gender,u.username');

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

        return $oDb->get($this->sTable . ' ' . $this->sTableAlias);
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
     **/
    protected function getCountCommonEmail($data = [])
    {
        if (!empty($data['keywords'])) {

            if (empty($data['or_like'])) {
                $data['or_like'] = [];
            }

            $data['or_like'][] = [
                'column' => $this->sTableAlias . '.ref',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->sTableAlias . '.user_id',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => $this->sTableAlias . '.user_email',
                'value'  => $data['keywords'],
            ];
            $data['or_like'][] = [
                'column' => 'ue.email',
                'value'  => $data['keywords'],
            ];

            $keywordAsId = (int) preg_replace('/[^0-9]/', '', $data['keywords']);

            if ($keywordAsId) {

                $data['or_like'][] = [
                    'column' => 'u.id',
                    'value'  => $keywordAsId,
                ];
            }
        }

        if (!empty($data['type'])) {

            if (empty($data['where'])) {
                $data['where'] = [];
            }

            $data['where'][] = [
                'column' => $this->sTableAlias . '.type',
                'value'  => $data['type'],
            ];
        }

        //  Common joins
        $oDb = Factory::service('Database');
        $oDb->join(NAILS_DB_PREFIX . 'user u', 'u.id = ' . $this->sTableAlias . '.user_id', 'LEFT');
        $oDb->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.email = ' . $this->sTableAlias . '.user_email', 'LEFT');

        $this->getCountCommon($data);
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of records in the archive
     *
     * @param array $data Data passed from the calling method
     *
     * @return mixed
     */
    public function countAll($data)
    {
        $this->getCountCommonEmail($data);
        $oDb = Factory::service('Database');
        return $oDb->count_all_results($this->sTable . ' ' . $this->sTableAlias);
    }

    // --------------------------------------------------------------------------

    /**
     * Get en email from the archive by its ID
     *
     * @param  int  $iId   The email's ID
     * @param array $aData The data array
     *
     * @return mixed   stdClass on success, false on failure
     */
    public function getById($iId, $aData = [])
    {
        if (empty($aData['where'])) {
            $aData['where'] = [];
        }

        $aData['where'][] = [$this->sTableAlias . '.id', $iId];

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
     */
    public function getByRef($sRef, $aData = [])
    {
        if (empty($aData['where'])) {
            $aData['where'] = [];
        }

        $aData['where'][] = [$this->sTableAlias . '.ref', $sRef];

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
        return md5($sGuid . APP_PRIVATE_KEY . $sRef);
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an attachment to an email
     *
     * @param string $file     The file's path
     * @param string $filename The filename ot give the attachment
     *
     * @return boolean
     */
    protected function addAttachment($file, $filename = null)
    {
        if (!file_exists($file)) {
            return false;
        }

        return $this->oCi->email->attach($file, 'attachment', $filename);
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a unique reference for an email, optionally exclude strings
     *
     * @param  array $exclude Strings to exclude from the reference
     *
     * @return string
     */
    protected function generateReference($exclude = [])
    {
        $oDb = Factory::service('Database');

        do {

            $refOk = false;

            do {

                $ref = strtoupper(random_string('alnum', 10));
                if (array_search($ref, $exclude) === false) {
                    $refOk = true;
                }

            } while (!$refOk);

            $oDb->where('ref', $ref);
            $result = $oDb->get($this->sTable);

        } while ($result->num_rows());

        return $ref;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the debugger
     *
     * @param  \stdClass $input         The email input object
     * @param  string    $body          The email's body
     * @param  string    $plaintext     The email's plaintext body
     * @param  array     $recent_errors An array of any recent errors
     *
     * @return void
     */
    protected function printDebugger($input, $body, $plaintext, $recent_errors)
    {
        /**
         * Debug mode, output data and don't actually send
         * Remove the reference to CI; takes up a ton'na space
         */

        if (isset($input->data['ci'])) {
            $input->data['ci'] = '**REFERENCE TO CODEIGNITER INSTANCE**';
        }

        // --------------------------------------------------------------------------

        //  Input variables
        echo '<pre>';

        //  Who's the email going to?
        echo '<strong>Sending to:</strong>' . "\n";
        echo '-----------------------------------------------------------------' . "\n";
        echo 'email: ' . $input->to->email . "\n";
        echo 'first: ' . $input->to->first . "\n";
        echo 'last:  ' . $input->to->last . "\n";

        //  Who's the email being sent from?
        echo "\n\n" . '<strong>Sending from:</strong>' . "\n";
        echo '-----------------------------------------------------------------' . "\n";
        echo 'name: ' . $input->from->name . "\n";
        echo 'email:    ' . $input->from->email . "\n";

        //  Input data (system & supplied)
        echo "\n\n" . '<strong>Input variables (supplied + system):</strong>' . "\n";
        echo '-----------------------------------------------------------------' . "\n";
        print_r($input->data);

        //  Template
        echo "\n\n" . '<strong>Email body:</strong>' . "\n";
        echo '-----------------------------------------------------------------' . "\n";
        echo 'Subject:         ' . $input->subject . "\n";
        echo 'Template Header: ' . $input->template_header . "\n";
        echo 'Template Body:   ' . $input->template_body . "\n";
        echo 'Template Footer: ' . $input->template_footer . "\n";

        if ($recent_errors) {

            echo "\n\n" . '<strong>Template Errors (' . count($recent_errors) . '):</strong>' . "\n";
            echo '-----------------------------------------------------------------' . "\n";

            foreach ($recent_errors as $error) {

                echo 'Severity: ' . $error->severity . "\n";
                echo 'Mesage: ' . $error->message . "\n";
                echo 'Filepath: ' . $error->filepath . "\n";
                echo 'Line: ' . $error->line . "\n\n";
            }
        }

        echo "\n\n" . '<strong>Rendered HTML:</strong>' . "\n";
        echo '-----------------------------------------------------------------' . "\n";

        $_rendered_body = str_replace('"', '\\"', $body);
        $_rendered_body = str_replace(["\r\n", "\r"], "\n", $_rendered_body);
        $_lines         = explode("\n", $_rendered_body);
        $_new_lines     = [];

        foreach ($_lines as $line) {

            if (!empty($line)) {

                $_new_lines[] = $line;
            }
        }

        $renderedBody  = implode($_new_lines);
        $entityBody    = htmlentities($body);
        $plaintextBody = nl2br($plaintext);

        $str = <<<EOT
<iframe width="100%" height="900" src="" id="renderframe"></iframe>
<script type="text/javascript">
var emailBody = "$renderedBody";
document.getElementById('renderframe').src = "data:text/html;charset=utf-8," + escape(emailBody);
</script>

<strong>HTML:</strong>
-----------------------------------------------------------------
$entityBody

<strong>Plain Text:</strong>
-----------------------------------------------------------------
</pre>$plaintextBody</pre>
EOT;
    }

    // --------------------------------------------------------------------------

    /**
     * Increments an email's open count and adds a tracking note
     *
     * @param  string $ref The email's reference
     *
     * @return void
     */
    public function trackOpen($ref)
    {
        $oEmail = $this->getByRef($ref);
        if ($oEmail) {

            //  Update the read count and a add a track data point
            $oDb = Factory::service('Database');
            $oDb->set('read_count', 'read_count+1', false);
            $oDb->where('id', $oEmail->id);
            $oDb->update($this->sTable);

            $oDb->set('created', 'NOW()', false);
            $oDb->set('email_id', $oEmail->id);

            if (activeUser('id')) {
                $oDb->set('user_id', activeUser('id'));
            }

            $oDb->insert(NAILS_DB_PREFIX . 'email_archive_track_open');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Increments a link's open count and adds a tracking note
     *
     * @param  string  $sRef    The email's reference
     * @param  integer $iLinkId The link's ID
     *
     * @return string
     */
    public function trackLink($sRef, $iLinkId)
    {
        $oEmail = $this->getByRef($sRef);

        if ($oEmail) {

            //  Get the link which was clicked
            $oDb = Factory::service('Database');
            $oDb->select('id, url');
            $oDb->where('email_id', $oEmail->id);
            $oDb->where('id', $iLinkId);
            $oLink = $oDb->get(NAILS_DB_PREFIX . 'email_archive_link')->row();

            if ($oLink) {

                //  Update the read count and a add a track data point
                $oDb->set('link_click_count', 'link_click_count+1', false);
                $oDb->where('id', $oEmail->id);
                $oDb->update($this->sTable);

                //  Add a link trackback
                $oDb->set('created', 'NOW()', false);
                $oDb->set('email_id', $oEmail->id);
                $oDb->set('link_id', $oLink->id);

                if (activeUser('id')) {
                    $oDb->set('user_id', activeUser('id'));
                }

                $oDb->insert(NAILS_DB_PREFIX . 'email_archive_track_link');

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
     * @param  string  $body          The string to parse
     * @param  int     $emailId       The email's ID
     * @param  string  $emailRef      The email's reference
     * @param  boolean $isHtml        Whether or not this is the HTML version of the email
     * @param  boolean $needsVerified Whether or not this user needs verified (i.e route tracking links through the
     *                                verifier)
     *
     * @return string
     */
    protected function parseLinks($body, $emailId, $emailRef, $isHtml = true, $needsVerified = false)
    {
        //    Set the class variables for the ID and ref (need those in the callbacks)
        $this->_generate_tracking_email_id       = $emailId;
        $this->_generate_tracking_email_ref      = $emailRef;
        $this->_generate_tracking_needs_verified = $needsVerified;

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
        $this->_generate_tracking_email_id       = null;
        $this->_generate_tracking_email_ref      = null;
        $this->_generate_tracking_needs_verified = null;

        // --------------------------------------------------------------------------

        return $body;
    }

    // --------------------------------------------------------------------------

    /**
     * Processes a link found by _parse_links()
     *
     * @param  array $link The link elements
     *
     * @return string
     */
    protected function processLinkHtml($link)
    {
        $_html  = !empty($link[0]) ? $link[0] : '';
        $_href  = !empty($link[1]) ? $link[1] : '';
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
     * @param  array $url The URL elements
     *
     * @return string
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
     * @param  string  $html    The Link HTML
     * @param  string  $url     The Link's URL
     * @param  string  $title   The Link's Title
     * @param  boolean $is_html Whether this is HTML or not
     *
     * @return string
     */
    protected function processLinkGenerate($html, $url, $title, $is_html)
    {
        //  Ensure URLs have a domain
        if (preg_match('/^\//', $url)) {
            $url = $this->sDomain . $url;
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

            if ($this->_generate_tracking_needs_verified) {

                //  Make sure we're not applying this to an activation URL
                if (!preg_match('#email/verify/[0-9]*?/(.*?)#', $url)) {

                    $_user_id = $this->_generate_tracking_needs_verified['id'];
                    $_code    = $this->_generate_tracking_needs_verified['code'];
                    $_return  = urlencode($url);

                    $_url = site_url('email/verify/' . $_user_id . '/' . $_code . '?return_to=' . $_return);

                } else {
                    $_url = $url;
                }

            } else {
                $_url = $url;
            }

            $oDb = Factory::service('Database');
            $oDb->set('email_id', $this->_generate_tracking_email_id);
            $oDb->set('url', $_url);
            $oDb->set('title', $title);
            $oDb->set('created', 'NOW()', false);
            $oDb->set('is_html', $is_html);
            $oDb->insert(NAILS_DB_PREFIX . 'email_archive_link');

            $_id = $oDb->insert_id();

            if ($_id) {

                $_time       = time();
                $trackingUrl = 'email/tracker/link/' . $this->_generate_tracking_email_ref . '/' . $_time . '/';
                $trackingUrl .= $this->generateHash($this->_generate_tracking_email_ref, $_time) . '/' . $_id;
                $trackingUrl = site_url($trackingUrl);

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
     * Format an email object
     *
     * @param  object $oEmail The raw email object
     *
     * @return void
     */
    protected function formatObject(&$oEmail)
    {
        $oEmail->type = !empty($this->aEmailType[$oEmail->type]) ? $this->aEmailType[$oEmail->type] : null;

        if (empty($oEmail->type)) {
            showFatalError('Invalid Email Type', 'Email with ID #' . $oEmail->id . ' has an invalid email type.');
        }

        // --------------------------------------------------------------------------

        //  Some fields can be manipulated by the contents of email_vars
        $oEmail->data = json_decode($oEmail->email_vars) ?: new \stdClass();
        unset($oEmail->email_vars);

        // --------------------------------------------------------------------------

        /**
         * If a subject is defined in the variables use that, if not check to see if one was
         * defined in the template; if not, fall back to a default subject
         */

        if (!empty($oEmail->data->email_subject)) {
            $oEmail->subject = $oEmail->data->email_subject;
        } elseif (!empty($oEmail->type->default_subject)) {
            $oEmail->subject = $oEmail->type->default_subject;
        } else {
            $oEmail->subject = 'An E-mail from ' . APP_NAME;
        }

        // --------------------------------------------------------------------------

        //  Template overrides
        if (!empty($oEmail->data->template_header)) {
            $oEmail->type->template_header = $oEmail->data->template_header;
        }

        if (!empty($oEmail->data->template_body)) {
            $oEmail->type->template_body = $oEmail->data->template_body;
        }

        if (!empty($oEmail->data->template_footer)) {
            $oEmail->type->template_footer = $oEmail->data->template_footer;
        }

        // --------------------------------------------------------------------------

        //  Who the email is being sent to
        $oEmail->to                      = new \stdClass();
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
        $oEmail->from        = new \stdClass();
        $oEmail->from->name  = !empty($oEmail->data->email_from_name) ? $oEmail->data->email_from_name : $this->from->name;
        $oEmail->from->email = !empty($oEmail->data->email_from_email) ? $oEmail->data->email_from_email : $this->from->email;

        //  Template details
        $oEmail->template               = new \stdClass();
        $oEmail->template->header       = new \stdClass();
        $oEmail->template->header->html = $oEmail->type->template_header;
        $oEmail->template->header->text = $oEmail->type->template_header . '_plaintext';
        $oEmail->template->body         = new \stdClass();
        $oEmail->template->body->html   = $oEmail->type->template_body;
        $oEmail->template->body->text   = $oEmail->type->template_body . '_plaintext';
        $oEmail->template->footer       = new \stdClass();
        $oEmail->template->footer->html = $oEmail->type->template_footer;
        $oEmail->template->footer->text = $oEmail->type->template_footer . '_plaintext';

        // --------------------------------------------------------------------------

        //  Add some extra, common variables for the template
        $oEmail->data->emailType = $oEmail->type;
        $oEmail->data->emailRef  = $oEmail->ref;
        $oEmail->data->sentFrom  = $oEmail->from;
        $oEmail->data->sentTo    = $oEmail->to;
        $oEmail->data->siteUrl   = site_url();

        //  Common URLs
        $oEmail->data->url = new \stdClass();

        //  AutoLogin
        $oEmail->data->url->autoLogin = '';

        // //  Check login URLs are allowed
        $this->oCi->config->load('auth/auth');

        if (!empty($oEmail->to->id) && $this->oCi->config->item('authEnableHashedLogin')) {
            $sIdHash                      = md5($oEmail->to->id);
            $sPwHash                      = md5($oEmail->to->password);
            $oEmail->data->url->autoLogin = site_url('auth/login/with_hashes/' . $sIdHash . '/' . $sPwHash);
        }

        //  View Online
        $iTime                         = time();
        $sHash                         = $this->generateHash($oEmail->data->emailRef, $iTime);
        $oEmail->data->url->viewOnline = site_url(
            'email/view/' . $oEmail->data->emailRef . '/' . $iTime . '/' . $sHash
        );

        //  1-Click Unsubscribe
        $oEmail->data->url->unsubscribe = '';
        if ($oEmail->type->isUnsubscribable && !empty($oEmail->to->id)) {

            $sUrl = site_url('email/unsubscribe?token=');

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
                    APP_PRIVATE_KEY
                );
                $iCounter++;

            } while ($iCounter <= $iAttempts && strpos($sToken, '+') !== false);

            //  Link, autologin if possible
            if (!empty($oEmail->data->url->autoLogin)) {
                $oEmail->data->url->unsubscribe = $oEmail->data->url->autoLogin . '?return_to=' . urlencode($sUrl . $sToken);
            } else {
                $oEmail->data->url->unsubscribe = $sUrl . $sToken;
            }
        }

        //  Tracker Image (not on view online links though)
        $oEmail->data->url->trackerImg = '';
        if (!preg_match('/^email\/view\/[a-zA-Z0-9]+\/[0-9]+\/[a-zA-Z0-9]+$/', uri_string())) {
            $iTime                         = time();
            $sHash                         = $this->generateHash($oEmail->data->emailRef, $iTime);
            $sImgSrc                       = site_url('email/tracker/' . $oEmail->data->emailRef . '/' . $iTime . '/' . $sHash) . '/0.gif';
            $oEmail->data->url->trackerImg = $sImgSrc;
        }

        // --------------------------------------------------------------------------

        $oMustache = Factory::service('Mustache');

        //  Subject
        $oEmail->subject = $oMustache->render($oEmail->subject, $oEmail->data);

        //  Add the rendered subject to the data array so the body can sue it
        $oEmail->data->email_subject = $oEmail->subject;

        //  Body
        $oEmail->body = new \stdClass();

        //  HTML Version
        $oView              = Factory::service('View');
        $oEmail->body->html = $oView->load(
            $oEmail->template->header->html,
            ['emailObject' => $oEmail],
            true
        );
        $oEmail->body->html .= $oView->load(
            $oEmail->template->body->html,
            ['emailObject' => $oEmail],
            true
        );
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
        $oEmail->body->text .= $oView->load(
            $oEmail->template->body->text,
            ['emailObject' => $oEmail],
            true
        );
        $oEmail->body->text .= $oView->load(
            $oEmail->template->footer->text,
            ['emailObject' => $oEmail],
            true
        );

        $oEmail->body->html = $oMustache->render($oEmail->body->html, $oEmail->data);
        $oEmail->body->text = $oMustache->render($oEmail->body->text, $oEmail->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Returns protected property $table
     * @return string
     */
    public function getTableName()
    {
        return $this->sTable;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns protected property $tableAlias
     * @return string
     */
    public function getTableAlias()
    {
        return $this->sTableAlias;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the defined sending from name, or falls back to APP_NAME
     * @return string
     */
    public function getFromName()
    {
        return appSetting('from_name', 'nails/module-email') ?: APP_NAME;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the defined sending from email, or falls back to nobody@host
     * @return string
     */
    public function getFromEmail()
    {
        return appSetting('from_email', 'nails/module-email') ?: 'nobody@' . gethostname();
    }
}
