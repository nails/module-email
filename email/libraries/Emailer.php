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

class Emailer
{
    //  Class traits
    use NAILS_COMMON_TRAIT_ERROR_HANDLING;

    //  Other vars
    public $from;
    private $ci;
    private $db;
    private $_email_type;
    private $_track_link_cache;

    // --------------------------------------------------------------------------

    /**
     * Construct the library
     * @param array $config An optional config array
     */
    public function __construct($config = array())
    {
        $this->ci   =& get_instance();
        $this->db   =& $this->ci->db;

        // --------------------------------------------------------------------------

        //  Set email related settings
        $this->from         = new stdClass();
        $this->from->name   = app_setting('from_name', 'email');
        $this->from->email  = app_setting('from_email', 'email');

        if (empty($this->from->name)) {

            $this->from->name = APP_NAME;
        }

        if (empty($this->from->email)) {

            $_url = parse_url(site_url());
            $this->from->email = 'nobody@' . $_url['host'];
        }

        // --------------------------------------------------------------------------

        //  Load Email library
        $this->ci->load->library('email');

        // --------------------------------------------------------------------------

        //  Load helpers
        $this->ci->load->helper('email');
        $this->ci->load->helper('typography');
        $this->ci->load->helper('string');

        // --------------------------------------------------------------------------

        //  Set defaults
        $this->_email_type       = array();
        $this->_track_link_cache = array();

        // --------------------------------------------------------------------------

        //  Define where we should look for email types
        $emailTypeLocations   = array();
        $emailTypeLocations[] = NAILS_COMMON_PATH . 'config/email_types.php';

        $modules = _NAILS_GET_AVAILABLE_MODULES();
        foreach ($modules as $module) {

            $moduleBits           = explode('-', $module);
            $emailTypeLocations[] = FCPATH . 'vendor/' . $module . '/' . $moduleBits[1] . '/config/email_types.php';
        }

        $emailTypeLocations[] = FCPATH . APPPATH . 'config/email_types.php';

        //  Find definitions
        foreach ($emailTypeLocations as $path) {

            $this->loadTypes($path);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Loads email types located in a config file at $path
     * @param  string $path The path to load
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
     * Adds a new email type to the stack
     * @param  stdClass $data An object representing the email type
     * @return boolean
     */
    protected function addType($data)
    {
        if (empty($data->slug) || empty($data->template_body)) {

            return false;
        }

        $temp                   = new stdClass();
        $temp->slug             = $data->slug;
        $temp->name             = $data->name;
        $temp->description      = $data->description;
        $temp->template_header  = empty($data->template_header) ? 'email/structure/header' : $data->template_header;
        $temp->template_body    = $data->template_body;
        $temp->template_footer  = empty($data->template_footer) ? 'email/structure/footer' : $data->template_footer;
        $temp->default_subject  = $data->default_subject;

        $this->_email_type[$data->slug] = $temp;

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Send an email
     * @param  object  $input    The email object
     * @param  boolean $graceful Whether to gracefully fail or not
     * @return void
     */
    public function send($input, $graceful = false)
    {
        //  We got something to work with?
        if (empty($input)) {

            $this->_set_error('EMAILER: No input');
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

            $this->_set_error('EMAILER: Missing user ID, user email or email type');
            return false;
        }

        // --------------------------------------------------------------------------

        //  If no email has been given make sure it's null
        if (empty($input->to_email)) {

            $input->to_email = null;
        }

        // --------------------------------------------------------------------------

        //  If no id has been given make sure it's null
        if (empty($input->to_id)) {

            $input->to_id = null;
        }

        // --------------------------------------------------------------------------

        //  If no internal_ref has been given make sure it's null
        if (empty($input->internal_ref)) {

            $input->internal_ref = null;
        }

        // --------------------------------------------------------------------------

        //  Make sure that at least empty data is available
        if (empty($input->data)) {

            $input->data = array();
        }

        // --------------------------------------------------------------------------

        //  Lookup the email type
        if (empty($this->_email_type[ $input->type ])) {

            if (!$graceful) {

                show_error('EMAILER: Invalid Email Type "' . $input->type . '"');

            } else {

                $this->_set_error('EMAILER: Invalid Email Type "' . $input->type . '"');
            }

            return false;
        }

        // --------------------------------------------------------------------------

        //  If we're sending to an email address, try and associate it to a registered user
        if ($input->to_email) {

            $_user = get_userobject()->get_by_email($input->to_email);

            if ($_user) {

                $input->to_id = $_user->id;
            }

        } else {

            //  Sending to an ID, fetch the user's email
            $_user = get_userobject()->get_by_id($input->to_id);

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

                show_error('EMAILER: No email address to send to.');

            } else {

                $this->_set_error('EMAILER: No email address to send to.');
                false;
            }
        }

        // --------------------------------------------------------------------------

        //  Add to the archive table
        $this->db->set('ref', $input->ref);
        $this->db->set('user_id', $input->to_id);
        $this->db->set('user_email', $input->to_email);
        $this->db->set('type', $input->type);
        $this->db->set('email_vars', serialize($input->data));
        $this->db->set('internal_ref', $input->internal_ref);

        $this->db->insert(NAILS_DB_PREFIX . 'email_archive');

        if ($this->db->affected_rows()) {

            $input->id = $this->db->insert_id();

        } else {

            if (!$graceful) {

                show_error('EMAILER: Insert Failed.');

            } else {

                $this->_set_error('EMAILER: Insert Failed.');
                false;
            }
        }

        if ($this->doSend($input->id, $graceful)) {

            //  Mail sent, mark the time
            $this->db->set('sent', 'NOW()', false);
            $this->db->where('id', $input->id);
            $this->db->update(NAILS_DB_PREFIX . 'email_archive');

            return $input->ref;

        } else {


            //  Mail failed, update the status
            $this->db->set('status', 'FAILED');
            $this->db->where('id', $input->id);
            $this->db->update(NAILS_DB_PREFIX . 'email_archive');

            return false;
        }
    }

    // --------------------------------------------------------------------------

    public function resend($emailIdRef)
    {
        if (is_numeric($emailIdRef)) {

            $email = $this->get_by_id($emailIdRef);

        } else {

            $email = $this->get_by_ref($emailIdRef);
        }

        if (!$email) {

            $this->_set_error('"' . $emailIdRef . '" is not a valid Email ID or reference.');
            return false;
        }

        $send           = new stdClass();
        $send->to_id    = $email->user->id;
        $send->to_email = $email->user->email;
        $send->type     = $email->type->slug;
        $send->data     = $email->email_vars;

        return $this->send($send);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether the user has unsubscribed from this email type
     * @param  int    $user_id The user ID to check for
     * @param  string $type    The type of email to check against
     * @return boolean
     */
    public function userHasUnsubscribed($user_id, $type)
    {
        $this->db->where('user_id', $user_id);
        $this->db->where('type', $type);

        return (bool) $this->db->count_all_results(NAILS_DB_PREFIX . 'user_email_blocker');
    }

    // --------------------------------------------------------------------------

    /**
     * Unsubscribes a user from a particular email type
     * @param  int    $user_id The user ID to unsubscribe
     * @param  string $type    The type of email to unsubscribe from
     * @return boolean
     */
    public function unsubscribeUser($user_id, $type)
    {
        if ($this->userHasUnsubscribed($user_id, $type)) {

            return true;
        }

        // --------------------------------------------------------------------------

        $this->db->set('user_id', $user_id);
        $this->db->set('type', $type);
        $this->db->set('created', 'NOW()', false);
        $this->db->insert(NAILS_DB_PREFIX . 'user_email_blocker');

        return (bool) $this->db->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Subscribe a user to a particular email type
     * @param  int    $user_id The user ID to subscribe
     * @param  string $type    The type of email to subscribe to
     * @return boolean
     */
    public function subscribeUser($user_id, $type)
    {
        if (!$this->userHasUnsubscribed($user_id, $type)) {

            return true;
        }

        // --------------------------------------------------------------------------

        $this->db->where('user_id', $user_id);
        $this->db->where('type', $type);
        $this->db->delete(NAILS_DB_PREFIX . 'user_email_blocker');

        return (bool) $this->db->affected_rows();
    }

    // --------------------------------------------------------------------------

    /**
     * Sends a templated email immediately
     * @param  int     $emailId The ID of the email to send, or the email object itself
     * @param  boolean $graceful Whether or not to faiul gracefully
     * @return boolean
     */
    protected function doSend($emailId = false, $graceful = false)
    {
        //  Get the email if $emailId is not an object
        if (is_numeric($emailId)) {

            $_email = $this->get_by_id($emailId);

            if (!$_email) {

                $this->_set_error('EMAILER: Invalid email ID');
                return false;
            }

        } elseif (is_object($emailId)) {

            $_email = $emailId;

        } else {

            $this->_set_error('EMAILER: Invalid email ID');
            return false;
        }

        // --------------------------------------------------------------------------

        $_send                          = new stdClass();
        $_send->to                      = new stdClass();
        $_send->to->email               = $_email->user->email;
        $_send->to->email_verified      = (bool) $_email->email_verified;
        $_send->to->email_verified_code = $_email->email_verified_code;
        $_send->to->first               = $_email->user->first_name;
        $_send->to->last                = $_email->user->last_name;
        $_send->to->id                  = (int) $_email->user->id;
        $_send->to->username            = $_email->user->username;
        $_send->to->group_id            = $_email->user->group_id;
        $_send->to->login_url           = $_email->user->id ? site_url('auth/login/with_hashes/' . md5($_email->user->id) . '/' . md5($_email->user->password)) : null;
        $_send->email_type              = $_email->type;
        $_send->subject                 = $_email->subject;
        $_send->template_header         = $_email->type->template_header;
        $_send->template_header_pt      = $_email->type->template_header . '_plaintext';
        $_send->template_body           = $_email->type->template_body;
        $_send->template_body_pt        = $_email->type->template_body . '_plaintext';
        $_send->template_footer         = $_email->type->template_footer;
        $_send->template_footer_pt      = $_email->type->template_footer . '_plaintext';
        $_send->data                    = $_email->email_vars;
        $_send->data['ci']              =& get_instance();

        //  Check login URLs are allowed
        get_instance()->config->load('auth/auth');

        if (!get_instance()->config->item('auth_enable_hashed_login')) {

            $_send->to->login_url = '';
        }

        if (!is_array($_send->data)) {

            $_send->data = array();
        }

        // --------------------------------------------------------------------------

        //  From user
        $_send->from = new stdClass();

        if (!empty($_send->data['email_from_email'])) {

            $_send->from->email = $_send->data['email_from_email'];
            $_send->from->name  = !empty($_send->data['email_from_name']) ? $_send->data['email_from_name'] : $_send->data['email_from_email'];

        } else {

            $_send->from->email = $this->from->email;
            $_send->from->name  = $this->from->name;
        }

        // --------------------------------------------------------------------------

        //  Fresh start please
        $this->ci->email->clear(true);

        // --------------------------------------------------------------------------

        //  Add some extra, common variables for the template
        $_send->data['email_type']    = $_email->type;
        $_send->data['email_ref']     = $_email->ref;
        $_send->data['sent_from']     = $_send->from;
        $_send->data['sent_to']       = $_send->to;
        $_send->data['email_subject'] = $_send->subject;
        $_send->data['site_url']      = site_url();
        $_send->data['secret']        = APP_PRIVATE_KEY;

        // --------------------------------------------------------------------------

        //  If we're not on a production server, never send out to any live addresses
        $_send_to = $_send->to->email;

        if (strtoupper(ENVIRONMENT) != 'PRODUCTION' || EMAIL_OVERRIDE) {

            if (EMAIL_OVERRIDE) {

                $_send_to = EMAIL_OVERRIDE;

            } elseif (APP_DEVELOPER_EMAIL) {

                $_send_to = APP_DEVELOPER_EMAIL;

            } else {

                //  Not sure where this is going; fall over *waaaa*
                show_error('EMAILER: Non production environment and neither EMAIL_OVERRIDE nor APP_DEVELOPER_EMAIL is set.');
                return false;
            }
        }

        // --------------------------------------------------------------------------

        //  Start prepping the email
        $this->ci->email->from($this->from->email, $_send->from->name);
        $this->ci->email->reply_to($_send->from->email, $_send->from->name);
        $this->ci->email->to($_send_to);
        $this->ci->email->subject($_send->subject);

        // --------------------------------------------------------------------------

        //  Clear any errors which might have happened previously
        $_error =& load_class('Exceptions', 'core');
        $_error->clear_errors();

        //  Load the template
        $body  = $this->ci->load->view($_send->template_header, $_send->data, true);
        $body .= $this->ci->load->view($_send->template_body, $_send->data, true);
        $body .= $this->ci->load->view($_send->template_footer, $_send->data, true);

        /**
         * If any errors occurred while attempting to generate the body of this email
         * then abort the sending and log it
         */

        if (EMAIL_DEBUG && APP_DEVELOPER_EMAIL && $_error->error_has_occurred()) {

            //  The templates error'd, abort the send and let dev know
            $_subject  = 'Email #' . $_email->id . ' failed to send due to errors occurring in the templates';
            $_message  = 'Hi,' . "\n";
            $_message .= '' . "\n";
            $_message .= 'Email #' . $_email->id . ' was aborted due to errors occurring while building the template' . "\n";
            $_message .= '' . "\n";
            $_message .= 'Please take a look as a matter of urgency; the errors are noted below:' . "\n";
            $_message .= '' . "\n";
            $_message .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $_message .= '' . "\n";

            $_errors = $_error->recent_errors();

            foreach ($_errors as $error) {

                $_message .= 'Severity: ' . $_error->levels[$error->severity] . "\n";
                $_message .= 'Message: ' . $error->message . "\n";
                $_message .= 'File: ' . $error->filepath . "\n";
                $_message .= 'Line: ' . $error->line . "\n";
                $_message .= '' . "\n";
            }

            $_message .= '' . "\n";
            $_message .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $_message .= '' . "\n";
            $_message .= 'Additional debugging information:' . "\n";
            $_message .= '' . "\n";
            $_message .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $_message .= '' . "\n";
            $_message .= print_r($_send, true) . "\n";

            sendDeveloperMail($_subject, $_message);

            // --------------------------------------------------------------------------

            $this->_set_error('EMAILER: Errors in email template, developers informed');

            // --------------------------------------------------------------------------]

            return false;
        }

        // --------------------------------------------------------------------------

        /**
         * Parse the body for <a> links and replace with a tracking URL
         * First clear out any previous link caches (production only)
         */

        $this->_track_link_cache = array();

        if (strtoupper(ENVIRONMENT) == 'PRODUCTION') {

            if ($_send->to->id && !$_send->to->email_verified) {

                $_needs_verified = array(
                    'id'   => $_send->to->id,
                    'code' => $_send->to->email_verified_code
                );

            } else {

                $_needs_verified = false;
            }

            $body = $this->parseLinks($body, $_email->id, $_email->ref, true, $_needs_verified);
        }

        // --------------------------------------------------------------------------

        //  Set the email body
        $this->ci->email->message($body);

        // --------------------------------------------------------------------------

        //  Set the plain text version
        $plaintext  = $this->ci->load->view($_send->template_header_pt, $_send->data, true);
        $plaintext .= $this->ci->load->view($_send->template_body_pt, $_send->data, true);
        $plaintext .= $this->ci->load->view($_send->template_footer_pt, $_send->data, true);

        // --------------------------------------------------------------------------

        //  Parse the body for URLs and replace with a tracking URL (production only)
        if (strtoupper(ENVIRONMENT) == 'PRODUCTION') {

            $plaintext = $this->parseLinks($plaintext, $_email->id, $_email->ref, false, $_needs_verified);
        }

        // --------------------------------------------------------------------------

        $this->ci->email->set_alt_message($plaintext);

        // --------------------------------------------------------------------------

        //  Add any attachments
        if (isset($_send->data['attachments']) && is_array($_send->data['attachments']) && $_send->data['attachments']) {

            foreach ($_send->data['attachments'] as $file) {

                /**
                 * TODO: Support for when custom names can be set.
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

                        show_error('EMAILER: Failed to add attachment: ' . $_file);

                    } else {

                        $this->_set_error('EMAILER: Insert Failed.');
                        return false;
                    }
                }

            }

        }

        // --------------------------------------------------------------------------

        //  Debugging?
        if (EMAIL_DEBUG) {

            $this->printDebugger($_send, $body, $plaintext, $_error->recent_errors());
            return false;
        }

        // --------------------------------------------------------------------------

        //  Send!Turn off error reporting, if it fails we should handle it gracefully
        $_previous_error_reporting = error_reporting();
        error_reporting(0);

        if ($this->ci->email->send()) {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            //  Update the counter on the email address
            $this->db->set('countSends', 'countSends+1', false);
            $this->db->where('email', $_send->to->email);
            $this->db->update(NAILS_DB_PREFIX . 'user_email');

            return true;

        } else {

            //  Put error reporting back as it was
            error_reporting($_previous_error_reporting);

            // --------------------------------------------------------------------------

            //  Failed to send, notify developers
            $_subject   = 'Email #' . $_email->id . ' failed to send at SMTP time';
            $_message   = 'Hi,' . "\n";
            $_message   .= '' . "\n";
            $_message   .= 'Email #' . $_email->id . ' failed to send at SMTP time' . "\n";
            $_message   .= '' . "\n";
            $_message   .= 'Please take a look as a matter of urgency; debugging data is below:' . "\n";
            $_message   .= '' . "\n";
            $_message   .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $_message   .= '' . "\n";

            $_message   .= $this->ci->email->print_debugger();

            $_message   .= '' . "\n";
            $_message   .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $_message   .= '' . "\n";
            $_message   .= 'Additional debugging information:' . "\n";
            $_message   .= '' . "\n";
            $_message   .= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
            $_message   .= '' . "\n";
            $_message   .= print_r($_send, true) . "\n";

            if (strtoupper(ENVIRONMENT) == 'PRODUCTION') {

                $this->_set_error('Email failed to send at SMTP time, developers informed');
                sendDeveloperMail($_subject, $_message);

            } else {

                /**
                 * On non-production environments halt execution, this is an error with the configs
                 * and should probably be addressed
                 */

                if (!$graceful) {

                    show_error('Email failed to send at SMTP time. Potential configuration error. Investigate, debugging data below: <div style="padding:20px;background:#EEE">' . $this->ci->email->print_debugger() . '</div>');

                } else {

                    $this->_set_error('Email failed to send at SMTP time.');
                }
            }

            // --------------------------------------------------------------------------

            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Gets an email from the archive
     * @param  string $order    The column to order on
     * @param  string $sort     The direction in which to order
     * @param  int    $offset   The offset
     * @param  int    $perPage  The number of records to show per page
     * @return array
     */
    public function get_all($order = null, $sort = null, $offset = null, $perPage = null)
    {
        //  Set defaults
        $order   = $order   ? $order   : 'ea.sent';
        $sort    = $sort    ? $sort    : 'ASC';
        $offset  = $offset  ? $offset  : 0;
        $perPage = $perPage ? $perPage : 25;

        // --------------------------------------------------------------------------

        $this->db->select('ea.id,ea.ref,ea.type,ea.email_vars,ea.user_email sent_to,ue.is_verified email_verified');
        $this->db->select('ue.code email_verified_code,ea.sent,ea.status,ea.read_count,ea.link_click_count');
        $this->db->select('u.first_name,u.last_name,u.id user_id,u.password user_password,u.group_id user_group');
        $this->db->select('u.profile_img,u.gender,u.username');

        $this->db->join(NAILS_DB_PREFIX . 'user u', 'u.id = ea.user_id', 'LEFT');
        $this->db->join(NAILS_DB_PREFIX . 'user_email ue', 'ue.email = ea.user_email', 'LEFT');

        $this->db->order_by($order, $sort);
        $this->db->order_by('ea.id', $sort);
        $this->db->limit($perPage, $offset);

        $emails = $this->db->get(NAILS_DB_PREFIX . 'email_archive ea')->result();

        // --------------------------------------------------------------------------

        //  Format emails
        foreach ($emails as $email) {

            $this->_format_email($email);
        }

        // --------------------------------------------------------------------------

        return $emails;
    }

    // --------------------------------------------------------------------------

    /**
     * Count the number of records in the archive
     * @return int
     */
    public function count_all()
    {
        return $this->db->count_all_results(NAILS_DB_PREFIX . 'email_archive');
    }

    // --------------------------------------------------------------------------

    /**
     * Get en email from the archive by its ID
     * @param  int $id The email's ID
     * @return mixed   stdClass on success, false on failure
     */
    public function get_by_id($id)
    {
        $this->db->where('ea.id', $id);
        $_item = $this->get_all();

        if (!$_item) {

            return false;
        }

        // --------------------------------------------------------------------------

        return $_item[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Get an email from the archive by its reference
     * @param  string  $ref  The email's reference
     * @param  string  $guid The email's GUID
     * @param  string  $hash The email's hash
     * @return mixed         stdClass on success, false on failure
     */
    public function get_by_ref($ref, $guid = false, $hash = false)
    {
        //  If guid and hash === false then by-pass the check
        if ($guid !== false && $hash !== false) {

            //  Check hash
            $_check = md5($guid . APP_PRIVATE_KEY . $ref);

            if ($_check !== $hash) {

                return 'BAD_HASH';
            }
        }

        // --------------------------------------------------------------------------

        $this->db->where('ea.ref', $ref);
        $_item = $this->get_all();

        if (!$_item) {

            return false;
        }

        return $_item[0];
    }

    // --------------------------------------------------------------------------

    /**
     * Adds an attachment to an email
     * @param string $file     The file's path
     * @param string $filename The filename ot give the attachment
     */
    protected function addAttachment($file, $filename = null)
    {
        if (!file_exists($file)) {

            return false;
        }

        if (!$this->ci->email->attach($file, 'attachment', $filename)) {

            return false;

        } else {

            return true;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Generates a unique reference for an email, optionally exclude strings
     * @param  array  $exclude Strings to exclude from the reference
     * @return string
     */
    protected function generateReference($exclude = array())
    {
        do {

            $_ref_ok = false;

            do {

                $ref = random_string('alnum', 10);
                if (array_search($ref, $exclude) === false) {

                    $_ref_ok = true;
                }

            } while (!$_ref_ok);

            $this->db->where('ref', $ref);
            $_query = $this->db->get(NAILS_DB_PREFIX . 'email_archive');

        } while ($_query->num_rows());

        // --------------------------------------------------------------------------

        return $ref;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders the debugger
     * @param  string $input         The email input object
     * @param  string $body          The email's body
     * @param  string $plaintext     The email's plaintext body
     * @param  array  $recent_errors An array of any recent errors
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
        $_rendered_body = str_replace(array("\r\n", "\r"), "\n", $_rendered_body);
        $_lines = explode("\n", $_rendered_body);
        $_new_lines = array();

        foreach ($_lines as $line) {

            if (!empty($line)) {

                $_new_lines[] = $line;
            }
        }

        $renderedBody  = implode($_new_lines);
        $entitiyBody   = htmlentities($body);
        $plaintextBody = nl2br($plaintext);

        $html  = '<iframe width="100%" height="900" src="" id="renderframe"></iframe>' . "\n";
        $html .= '<script type="text/javascript">' . "\n";
        $html .= 'var emailBody = "{$renderedBody}";' . "\n";
        $html .= 'document.getElementById(\'renderframe\').src = "data:text/html;charset=utf-8," + escape(emailBody);' . "\n";
        $html .= '</script>' . "\n";
        $html .= "\n";
        $html .= '<strong>HTML:</strong>' . "\n";
        $html .= '-----------------------------------------------------------------' . "\n";
        $html .= $entitiyBody . "\n";
        $html .= "\n";
        $html .= '<strong>Plain Text:</strong>' . "\n";
        $html .= '-----------------------------------------------------------------' . "\n";
        $html .= '</pre>' . $plaintextBody . "\n";

        echo $html;
    }

    // --------------------------------------------------------------------------

    /**
     * Increments an email's open count and adds a tracking note
     * @param  string $ref  The email's reference
     * @param  string $guid The email's GUID
     * @param  string $hash The email's hash
     * @return boolean
     */
    public function trackOpen($ref, $guid, $hash)
    {
        $_email = $this->get_by_ref($ref, $guid, $hash);

        if ($_email && $_email != 'BAD_HASH') {

            //  Update the read count and a add a track data point
            $this->db->set('read_count', 'read_count+1', false);
            $this->db->where('id', $_email->id);
            $this->db->update(NAILS_DB_PREFIX . 'email_archive');

            $this->db->set('created', 'NOW()', false);
            $this->db->set('email_id', $_email->id);

            if (active_user('id')) {

                $this->db->set('user_id', active_user('id'));
            }

            $this->db->insert(NAILS_DB_PREFIX . 'email_archive_track_open');

            return true;
        }

        return false;
    }

    // --------------------------------------------------------------------------

    /**
     * Increments a link's open count and adds a tracking note
     * @param  string $ref     The email's reference
     * @param  string $guid    The email's GUID
     * @param  string $hash    The email's hash
     * @param  string $link_id The link's ID
     * @return string
     */
    public function trackLink($ref, $guid, $hash, $link_id)
    {
        $_email = $this->get_by_ref($ref, $guid, $hash);

        if ($_email && $_email != 'BAD_HASH') {

            //  Get the link which was clicked
            $this->db->select('url');
            $this->db->where('email_id', $_email->id);
            $this->db->where('id', $link_id);
            $_link = $this->db->get(NAILS_DB_PREFIX . 'email_archive_link')->row();

            if ($_link) {

                //  Update the read count and a add a track data point
                $this->db->set('link_click_count', 'link_click_count+1', false);
                $this->db->where('id', $_email->id);
                $this->db->update(NAILS_DB_PREFIX . 'email_archive');

                //  Add a link trackback
                $this->db->set('created', 'NOW()', false);
                $this->db->set('email_id', $_email->id);
                $this->db->set('link_id', $link_id);

                if (active_user('id')) {

                    $this->db->set('user_id', active_user('id'));
                }

                $this->db->insert(NAILS_DB_PREFIX . 'email_archive_track_link');

                //  Return the URL to go to
                return $_link->url;

            } else {

                return 'BAD_LINK';
            }
        }

        return 'BAD_HASH';
    }

    // --------------------------------------------------------------------------

    /**
     * Parses a string for <a> links and replaces them with a trackable URL
     * @param  string  $body           The string to parse
     * @param  int     $emailId       The email's ID
     * @param  string  $emailRef      The email's reference
     * @param  boolean $isHtml        Whether or not this is the HTML version of the email
     * @param  boolean $needsVerified Whether or not this user needs verified (i.e route tracking links through the verifier)
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

            $pattern    = '/<a .*?(href="(https?.*?)").*?>(.*?)<\/a>/';
            $body       = preg_replace_callback($pattern, array($this, 'processLinkHtml'), $body);

        } else {

            $pattern    = '/(https?:\/\/)([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?/';
            $body       = preg_replace_callback($pattern, array($this, 'processLinkUrl'), $body);
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
     * @param  array $link The link elements
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
     * @param  array $url The URL elements
     * @return string
     */
    protected function processLinkUrl($url)
    {
        $_html  = !empty($url[0]) ? $url[0] : '';
        $_url   = $_html;
        $_title = $_html;

        // --------------------------------------------------------------------------

        //  Only process if theres a URL to process
        if ($_html && $_url) {

            $_html = $this->processLinkGenerate($_html, $_url, $_title, false);
        }

        return $_html;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate a tracking URL
     * @param  string  $html    The Link HTML
     * @param  string  $url     The Link's URL
     * @param  string  $title   The Link's Title
     * @param  boolean $is_html Whether this is HTML or not
     * @return string
     */
    protected function processLinkGenerate($html, $url, $title, $is_html)
    {
        /**
         * Generate a tracking URL for this link
         * Firstly, check this URL hasn't been processed already (for this email)
         */

        if (isset($this->_track_link_cache[md5($url)])) {

            $trackingUrl = $this->_track_link_cache[md5($url)];

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

            $this->db->set('email_id', $this->_generate_tracking_email_id);
            $this->db->set('url', $_url);
            $this->db->set('title', $title);
            $this->db->set('created', 'NOW()', false);
            $this->db->set('is_html', $is_html);
            $this->db->insert(NAILS_DB_PREFIX . 'email_archive_link');

            $_id = $this->db->insert_id();

            if ($_id) {

                $_time        = time();
                $trackingUrl  = 'email/tracker/link/' . $this->_generate_tracking_email_ref . '/' . $_time . '/';
                $trackingUrl .= md5($_time . APP_PRIVATE_KEY . $this->_generate_tracking_email_ref). '/' . $_id;
                $trackingUrl  = site_url($trackingUrl);

                $this->_track_link_cache[md5($url)] = $trackingUrl;

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
     * @param  object $email The raw email object
     * @return void
     */
    protected function _format_email(&$email)
    {
        $email->email_vars = @unserialize($email->email_vars);
        $email->type       = !empty($this->_email_type[$email->type]) ? $this->_email_type[$email->type] : null;

        if (empty($email->type)) {

            showFatalError('Invalid Email Type', 'Email with ID #' . $email->id . ' has an invalid email type.');
        }

        // --------------------------------------------------------------------------

        /**
         * If a subject is defined in the variables use that, if not check to see if one was
         * defined in the template; if not, fall back to a default subject
         */

        if (!empty($email->email_vars['email_subject'])) {

            $email->subject = $email->email_vars['email_subject'];

        } elseif (!empty($email->type->default_subject)) {

            $email->subject = $email->type->default_subject;

        } else {

            $email->subject = 'An E-mail from ' . APP_NAME;
        }

        // --------------------------------------------------------------------------

        //  Template overrides
        if (!empty($email->email_vars['template_header'])) {

            $email->type->template_body = $email->email_vars['template_header'];
        }

        if (!empty($email->email_vars['template_body'])) {

            $email->type->template_body = $email->email_vars['template_body'];
        }

        if (!empty($email->email_vars['template_footer'])) {

            $email->type->template_body = $email->email_vars['template_footer'];
        }

        // --------------------------------------------------------------------------

        //  Sent to
        $email->user              = new stdClass();
        $email->user->id          = $email->user_id;
        $email->user->group_id    = $email->user_group;
        $email->user->email       = $email->sent_to;
        $email->user->username    = $email->username;
        $email->user->password    = $email->user_password;
        $email->user->first_name  = $email->first_name;
        $email->user->last_name   = $email->last_name;
        $email->user->profile_img = $email->profile_img;
        $email->user->gender      = $email->gender;

        unset($email->user_id);
        unset($email->sent_to);
        unset($email->username);
        unset($email->first_name);
        unset($email->last_name);
        unset($email->profile_img);
        unset($email->gender);
        unset($email->user_group);
        unset($email->user_password);
    }
}
