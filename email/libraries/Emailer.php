<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Name:			Emailer
*
* Description:	Easily send email within apps
*
*/

class Emailer
{
	//	Class traits
	use NAILS_COMMON_TRAIT_ERROR_HANDLING;

	//	Other vars
	public  $from;
	private $ci;
	private $db;
	private $_email_type;
	private $_track_link_cache;


	// --------------------------------------------------------------------------


	/**
	 * Construct the library
	 * @param array $config An optional config array
	 */
	public function __construct( $config = array() )
	{
		$this->ci	=& get_instance();
		$this->db	=& $this->ci->db;

		// --------------------------------------------------------------------------

		//	Set email related settings
		$this->from			= new stdClass();
		$this->from->name	= app_setting( 'from_name', 'email' );
		$this->from->email	= app_setting( 'from_email', 'email' );

		if ( empty( $this->from->name ) ) :

			$this->from->name = APP_NAME;

		endif;

		if ( empty( $this->from->email ) ) :

			$_url = parse_url( site_url() );
			$this->from->email = 'nobody@' . $_url['host'];

		endif;

		// --------------------------------------------------------------------------

		//	Load Email library
		$this->ci->load->library( 'email' );

		// --------------------------------------------------------------------------

		//	Load helpers
		$this->ci->load->helper( 'email' );
		$this->ci->load->helper( 'typography' );
		$this->ci->load->helper( 'string' );

		// --------------------------------------------------------------------------

		//	Set defaults
		$this->_email_type			= array();
		$this->_track_link_cache	= array();

		// --------------------------------------------------------------------------

		//	Look for email types defined by enabled modules
		$_modules = _NAILS_GET_AVAILABLE_MODULES();

		foreach( $_modules AS $module ) :

			$_module	= explode( '-', $module );
			$_path		= FCPATH . 'vendor/' . $module . '/' . $_module[1] . '/config/email_types.php';

			if ( file_exists( $_path ) ) :

				include $_path;

				if ( ! empty( $config['email_types'] ) ) :

					foreach( $config['email_types'] AS $type ) :

						$this->add_type( $type );

					endforeach;

				endif;

			endif;

		endforeach;

		//	Finally, look for app email types
		$_path = FCPATH . APPPATH . 'config/email_types.php';

		if ( file_exists( $_path ) ) :

			include $_path;

			if ( ! empty( $config['email_types'] ) ) :

				foreach( $config['email_types'] AS $type ) :

					$this->add_type( $type );

				endforeach;

			endif;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Adds a new email type to the stack
	 * @param mixed $slug             The email's slug; calling code refers to emails by this value. Alternatively pass a stdClass to set all values.
	 * @param string $name            The human friendly name to give the email
	 * @param string $description     The human friendly description of the email's purpose
	 * @param string $template_body   The view to use for the email's body
	 * @param string $template_header The view to use for the email's header
	 * @param string $template_footer The view to use for the email's footer
	 * @param string $default_subject The default subject to give the email
	 */
	public function add_type( $slug, $name = '', $description = '', $template_body = '', $template_header = '', $template_footer = '', $default_subject = '' )
	{
		if ( is_string( $slug ) ) :

			if ( empty( $slug ) || empty( $template_body ) ) :

				return FALSE;

			endif;

			$this->_email_type[$slug]					= new stdClass();
			$this->_email_type[$slug]->slug				= $slug;
			$this->_email_type[$slug]->name				= $name;
			$this->_email_type[$slug]->description		= $description;
			$this->_email_type[$slug]->template_header	= empty( $template_header ) ? 'email/structure/header' : $slug->template_header;
			$this->_email_type[$slug]->template_body	= $template_body;
			$this->_email_type[$slug]->template_footer	= empty( $template_footer ) ? 'email/structure/footer' : $slug->template_footer;
			$this->_email_type[$slug]->default_subject	= $default_subject;

		else :

			if ( empty( $slug->slug ) || empty( $slug->template_body ) ) :

				return FALSE;

			endif;

			$this->_email_type[$slug->slug]						= new stdClass();
			$this->_email_type[$slug->slug]->slug				= $slug->slug;
			$this->_email_type[$slug->slug]->name				= $slug->name;
			$this->_email_type[$slug->slug]->description		= $slug->description;
			$this->_email_type[$slug->slug]->template_header	= empty( $slug->template_header ) ? 'email/structure/header' : $slug->template_header;
			$this->_email_type[$slug->slug]->template_body		= $slug->template_body;
			$this->_email_type[$slug->slug]->template_footer	= empty( $slug->template_footer ) ? 'email/structure/footer' : $slug->template_footer;
			$this->_email_type[$slug->slug]->default_subject	= $slug->default_subject;

		endif;

		return TRUE;
	}


	// --------------------------------------------------------------------------


	/**
	 * Send an email
	 * @param  object  $input    The email object
	 * @param  boolean $graceful Whether to gracefully fail or not
	 * @return void
	 */
	public function send( $input, $graceful = FALSE )
	{
		//	We got something to work with?
		if ( empty( $input ) ) :

			$this->_set_error( 'EMAILER: No input' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Ensure $input is an object
		if ( ! is_object( $input ) ) :

			$input = (object) $input;

		endif;

		// --------------------------------------------------------------------------

		//	Check we have at least a user_id/email and an email type
		if ( ( empty( $input->to_id ) && empty( $input->to_email ) ) || empty( $input->type ) ) :

			$this->_set_error( 'EMAILER: Missing user ID, user email or email type' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	If no email has been given make sure it's NULL
		if ( empty( $input->to_email ) ) :

			$input->to_email = NULL;

		endif;

		// --------------------------------------------------------------------------

		//	If no id has been given make sure it's NULL
		if ( empty( $input->to_id ) ) :

			$input->to_id = NULL;

		endif;

		// --------------------------------------------------------------------------

		//	If no internal_ref has been given make sure it's NULL
		if ( empty( $input->internal_ref ) ) :

			$input->internal_ref = NULL;

		endif;

		// --------------------------------------------------------------------------

		//	Make sure that at least empty data is available
		if ( empty( $input->data ) ) :

			$input->data = array();

		endif;

		// --------------------------------------------------------------------------

		//	Lookup the email type
		if ( empty( $this->_email_type[ $input->type ] ) ) :

			if ( ! $graceful ) :

				show_error( 'EMAILER: Invalid Email Type "' . $input->type . '"' );

			else :

				$this->_set_error( 'EMAILER: Invalid Email Type "' . $input->type . '"' );

			endif;

			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	If we're sending to an email address, try and associate it to a registered user
		if ( $input->to_email ) :

			$_user = get_userobject()->get_by_email( $input->to_email );

			if ( $_user ) :

				$input->to_id = $_user->id;

			endif;

		else :

			//	Sending to an ID, fetch the user's email
			$_user = get_userobject()->get_by_id( $input->to_id );

			if ( ! empty( $_user->email ) ) :

				$input->to_email = $_user->email;

			endif;

		endif;

		// --------------------------------------------------------------------------

		//	Check to see if the user has opted out of receiving these emails
		if ( $input->to_id ) :

			if ( $this->user_has_unsubscribed( $input->to_id, $input->type ) ) :

				//	User doesn't want to receive these notifications; abort.
				return TRUE;

			endif;

		endif;

		// --------------------------------------------------------------------------

		/**
		 * Generate a unique reference - ref is sent in each email and can allow the
		 * system to generate 'view online' links
		 */

		$input->ref = $this->_generate_reference();

		// --------------------------------------------------------------------------

		/**
		 * Double check we have an email address (a user may exist but not have an
		 * email address set)
		 */

		if ( empty( $input->to_email ) ) :

			if ( ! $graceful ) :

				show_error( 'EMAILER: No email address to send to.' );

			else :

				$this->_set_error( 'EMAILER: No email address to send to.' );
				FALSE;

			endif;


		endif;

		// --------------------------------------------------------------------------

		//	Add to the archive table
		$this->db->set( 'ref',			$input->ref );
		$this->db->set( 'user_id',		$input->to_id );
		$this->db->set( 'user_email',	$input->to_email );
		$this->db->set( 'type',			$input->type );
		$this->db->set( 'email_vars',	serialize( $input->data ) );
		$this->db->set( 'internal_ref',	$input->internal_ref );

		$this->db->insert( NAILS_DB_PREFIX . 'email_archive' );

		if ( $this->db->affected_rows() ) :

			$input->id = $this->db->insert_id();

		else :

			if ( ! $graceful ) :

				show_error( 'EMAILER: Insert Failed.' );

			else :

				$this->_set_error( 'EMAILER: Insert Failed.' );
				FALSE;

			endif;

		endif;

		if ( $this->_send( $input->id, $graceful ) ) :

			return $input->ref;

		else :

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Determines whether the user has unsubscribed from this email type
	 * @param  int    $user_id The user ID to check for
	 * @param  string $type    The type of email to check against
	 * @return boolean
	 */
	public function user_has_unsubscribed( $user_id, $type )
	{
		$this->db->where( 'user_id', $user_id );
		$this->db->where( 'type', $type );

		return (bool) $this->db->count_all_results( NAILS_DB_PREFIX . 'user_email_blocker' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Unsubscribes a user from a particular email type
	 * @param  int    $user_id The user ID to unsubscribe
	 * @param  string $type    The type of email to unsubscribe from
	 * @return boolean
	 */
	public function unsubscribe_user( $user_id, $type )
	{
		if ( $this->user_has_unsubscribed( $user_id, $type ) ) :

			return TRUE;

		endif;

		// --------------------------------------------------------------------------

		$this->db->set( 'user_id', $user_id );
		$this->db->set( 'type', $type );
		$this->db->set( 'created', 'NOW()', FALSE );
		$this->db->insert( NAILS_DB_PREFIX . 'user_email_blocker' );

		return (bool) $this->db->affected_rows();
	}


	// --------------------------------------------------------------------------


	/**
	 * Subscribe a user to a aprticular email type
	 * @param  int    $user_id The user ID to subscribe
	 * @param  string $type    The type of email to subscribe to
	 * @return boolean
	 */
	public function subscribe_user( $user_id, $type )
	{
		if ( ! $this->user_has_unsubscribed( $user_id, $type ) ) :

			return TRUE;

		endif;

		// --------------------------------------------------------------------------

		$this->db->where( 'user_id', $user_id );
		$this->db->where( 'type', $type );
		$this->db->delete( NAILS_DB_PREFIX . 'user_email_blocker' );

		return (bool) $this->db->affected_rows();
	}


	// --------------------------------------------------------------------------


	/**
	 * Sends a templated email immediately
	 * @param  int     $email_id The ID of the email to send, or the email object itself
	 * @param  boolean $graceful Whether or not to faiul gracefully
	 * @return boolean
	 */
	private function _send( $email_id = FALSE, $graceful = FALSE )
	{
		//	Get the email if $email_id is not an object
		if ( ! is_object( $email_id ) ) :

			$_email = $this->get_by_id( $email_id );

		else :

			$_email = $email_id;

		endif;

		// --------------------------------------------------------------------------

		if ( ! $_email ) :

			$this->_set_error( 'EMAILER: Unable to fetch email object' );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		$_send							= new stdClass();
		$_send->to						= new stdClass();
		$_send->to->email				= $_email->user->email;
		$_send->to->email_verified		= (bool) $_email->email_verified;
		$_send->to->email_verified_code	= $_email->email_verified_code;
		$_send->to->first				= $_email->user->first_name;
		$_send->to->last				= $_email->user->last_name;
		$_send->to->id					= (int) $_email->user->id;
		$_send->to->username			= $_email->user->username;
		$_send->to->group_id			= $_email->user->group_id;
		$_send->to->login_url			= $_email->user->id ? site_url( 'auth/login/with_hashes/' . md5( $_email->user->id ) . '/' . md5( $_email->user->password ) ) : NULL;
		$_send->email_type				= $_email->type;
		$_send->subject					= $_email->subject;
		$_send->template_header			= $_email->type->template_header;
		$_send->template_header_pt		= $_email->type->template_header . '_plaintext';
		$_send->template_body			= $_email->type->template_body;
		$_send->template_body_pt		= $_email->type->template_body . '_plaintext';
		$_send->template_footer			= $_email->type->template_footer;
		$_send->template_footer_pt		= $_email->type->template_footer . '_plaintext';
		$_send->data					= $_email->email_vars;
		$_send->data['ci']				=& get_instance();

		//	Check login URLs are allowed
		get_instance()->config->load( 'auth/auth' );

		if ( ! get_instance()->config->item( 'auth_enable_hashed_login' ) ) :

			$_send->to->login_url = '';

		endif;

		if ( ! is_array( $_send->data ) ) :

			$_send->data = array();

		endif;

		// --------------------------------------------------------------------------

		//	From user
		$_send->from = new stdClass();

		if ( ! empty( $_send->data['email_from_email'] ) ) :

			$_send->from->email	= $_send->data['email_from_email'];
			$_send->from->name	= ! empty( $_send->data['email_from_name'] ) ? $_send->data['email_from_name'] : $_send->data['email_from_email'];

		else :

			$_send->from->email	= $this->from->email;
			$_send->from->name	= $this->from->name;

		endif;

		// --------------------------------------------------------------------------

		//	Fresh start please
		$this->ci->email->clear( TRUE );

		// --------------------------------------------------------------------------

		//	Add some extra, common variables for the template
		$_send->data['email_type']		= $_email->type;
		$_send->data['email_ref']		= $_email->ref;
		$_send->data['sent_from']		= $_send->from;
		$_send->data['sent_to']			= $_send->to;
		$_send->data['email_subject']	= $_send->subject;
		$_send->data['site_url']		= site_url();
		$_send->data['secret']			= APP_PRIVATE_KEY;

		// --------------------------------------------------------------------------

		//	If we're not on a production server, never send out to any live addresses
		$_send_to = $_send->to->email;

		if ( strtoupper( ENVIRONMENT ) != 'PRODUCTION' || EMAIL_OVERRIDE ) :

			if ( EMAIL_OVERRIDE ) :

				$_send_to = EMAIL_OVERRIDE;

			elseif ( APP_DEVELOPER_EMAIL ) :

				$_send_to = APP_DEVELOPER_EMAIL;

			else :

				//	Not sure where this is going; fall over *waaaa*
				show_error( 'EMAILER: Non production environment and neither EMAIL_OVERRIDE nor APP_DEVELOPER_EMAIL is set.' );
				return FALSE;

			endif;

		endif;

		// --------------------------------------------------------------------------

		//	Start prepping the email
		$this->ci->email->from( $this->from->email, $_send->from->name );
		$this->ci->email->reply_to( $_send->from->email, $_send->from->name );
		$this->ci->email->to( $_send_to );
		$this->ci->email->subject( $_send->subject );

		// --------------------------------------------------------------------------

		//	Clear any errors which might have happened previously
		$_error =& load_class( 'Exceptions', 'core' );
		$_error->clear_errors();

		//	Load the template
		$body  = $this->ci->load->view( $_send->template_header,	$_send->data, TRUE );
		$body .= $this->ci->load->view( $_send->template_body,		$_send->data, TRUE );
		$body .= $this->ci->load->view( $_send->template_footer,	$_send->data, TRUE );

		/**
		 * If any errors occurred while attempting to generate the body of this email
		 * then abort the sending and log it
		 */

		if ( EMAIL_DEBUG && APP_DEVELOPER_EMAIL && $_error->error_has_occurred() ) :

			//	The templates error'd, abort the send and let dev know
			$_subject	= 'Email #' . $_email->id . ' failed to send due to errors occurring in the templates';
			$_message	= 'Hi,' . "\n";
			$_message	.= '' . "\n";
			$_message	.= 'Email #' . $_email->id . ' was aborted due to errors occurring while building the template' . "\n";
			$_message	.= '' . "\n";
			$_message	.= 'Please take a look as a matter of urgency; the errors are noted below:' . "\n";
			$_message	.= '' . "\n";
			$_message	.= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
			$_message	.= '' . "\n";

			$_errors = $_error->recent_errors();

			foreach ( $_errors AS $error ) :

				$_message	.= 'Severity: ' . $_error->levels[$error->severity] . "\n";
				$_message	.= 'Message: ' . $error->message . "\n";
				$_message	.= 'File: ' . $error->filepath . "\n";
				$_message	.= 'Line: ' . $error->line . "\n";
				$_message	.= '' . "\n";

			endforeach;

			$_message	.= '' . "\n";
			$_message	.= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
			$_message	.= '' . "\n";
			$_message	.= 'Additional debugging information:' . "\n";
			$_message	.= '' . "\n";
			$_message	.= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
			$_message	.= '' . "\n";
			$_message	.= print_r( $_send, TRUE ) . "\n";

			send_developer_mail( $_subject, $_message );

			// --------------------------------------------------------------------------

			$this->_set_error( 'EMAILER: Errors in email template, developers informed' );

			// --------------------------------------------------------------------------]

			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		/**
		 * Parse the body for <a> links and replace with a tracking URL
		 * First clear out any previous link caches (production only)
		 */

        $this->_track_link_cache = array();

        if (strtoupper(ENVIRONMENT) == 'PRODUCTION') {

            if ($_send->to->id && ! $_send->to->email_verified) :

                $_needs_verified = array(
                    'id'    => $_send->to->id,
                    'code'  => $_send->to->email_verified_code
                );

            else :

                $_needs_verified = false;

            endif;

            $body = $this->_parse_links($body, $_email->id, $_email->ref, true, $_needs_verified);
        }

		// --------------------------------------------------------------------------

		//	Set the email body
		$this->ci->email->message( $body );

		// --------------------------------------------------------------------------

		//	Set the plain text version
		$plaintext  = $this->ci->load->view( $_send->template_header_pt,	$_send->data, TRUE );
		$plaintext .= $this->ci->load->view( $_send->template_body_pt,		$_send->data, TRUE );
		$plaintext .= $this->ci->load->view( $_send->template_footer_pt,	$_send->data, TRUE );

		// --------------------------------------------------------------------------

		//	Parse the body for URLs and replace with a tracking URL (production only)
		if ( strtoupper( ENVIRONMENT ) == 'PRODUCTION' ) :

			$plaintext = $this->_parse_links( $plaintext, $_email->id, $_email->ref, FALSE, $_needs_verified );

		endif;

		// --------------------------------------------------------------------------

		$this->ci->email->set_alt_message( $plaintext );

		// --------------------------------------------------------------------------

		//	Add any attachments
		if ( isset( $_send->data['attachments'] ) && is_array( $_send->data['attachments'] ) && $_send->data['attachments'] ) :

			foreach ( $_send->data['attachments'] AS $file ) :

				/**
				 * TODO: Support for when custom names can be set.
				 * It's in the CI 3.0 dev branch, wonder if it'll ever be
				 * released.
				 */

				if ( is_array( $file ) ) :

					$_file		= isset( $file[0] ) ? $file[0] : NULL;
					$_filename	= isset( $file[1] ) ? $file[1] : NULL;

				else :

					$_file		= $file;
					$_filename	= NULL;

				endif;

				//	In case custom names support is added
				if ( ! $this->_add_attachment( $_file, $_filename ) ) :

					if ( ! $graceful ) :

						show_error( 'EMAILER: Failed to add attachment: ' . $_file );

					else :

						$this->_set_error( 'EMAILER: Insert Failed.' );
						return FALSE;

					endif;

				endif;

			endforeach;

		endif;

		// --------------------------------------------------------------------------

		//	Debugging?
		if ( EMAIL_DEBUG ) :

			$this->_debugger( $_send, $body, $plaintext, $_error->recent_errors() );
			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		//	Send! Turn off error reporting, if it fails we should handle it gracefully
		$_previous_error_reporting = error_reporting();
		error_reporting(0);

		if ( $this->ci->email->send() ) :

			//	Put error reporting back as it was
			error_reporting( $_previous_error_reporting );

			// --------------------------------------------------------------------------

			//	Mail sent, mark the time
			$this->db->set( 'time_sent', 'NOW()', FALSE );
			$this->db->where( 'id', $_email->id );
			$this->db->update( NAILS_DB_PREFIX . 'email_archive' );

			return TRUE;

		else:

			//	Put error reporting back as it was
			error_reporting( $_previous_error_reporting );

			// --------------------------------------------------------------------------

			//	Failed to send, notify developers
			$_subject	= 'Email #' . $_email->id . ' failed to send at SMTP time';
			$_message	= 'Hi,' . "\n";
			$_message	.= '' . "\n";
			$_message	.= 'Email #' . $_email->id . ' failed to send at SMTP time' . "\n";
			$_message	.= '' . "\n";
			$_message	.= 'Please take a look as a matter of urgency; debugging data is below:' . "\n";
			$_message	.= '' . "\n";
			$_message	.= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
			$_message	.= '' . "\n";

			$_message	.= $this->ci->email->print_debugger();

			$_message	.= '' . "\n";
			$_message	.= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
			$_message	.= '' . "\n";
			$_message	.= 'Additional debugging information:' . "\n";
			$_message	.= '' . "\n";
			$_message	.= '- - - - - - - - - - - - - - - - - - - - - -' . "\n";
			$_message	.= '' . "\n";
			$_message	.= print_r( $_send, TRUE ) . "\n";

			if ( strtoupper( ENVIRONMENT ) == 'PRODUCTION' ) :

				$this->_set_error( 'Email failed to send at SMTP time, developers informed' );
				send_developer_mail( $_subject, $_message );

			else :

				/**
				 * On non-production environments halt execution, this is an error with the configs
				 * and should probably be addressed
				 */

				if ( ! $graceful ) :

					show_error( 'Email failed to send at SMTP time. Potential configuration error. Investigate, debugging data below: <div style="padding:20px;background:#EEE">' . $this->ci->email->print_debugger() . '</div>' );

				else :

					$this->_set_error( 'Email failed to send at SMTP time.' );

				endif;

			endif;

			// --------------------------------------------------------------------------

			return FALSE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets an email from the archive
	 * @param  string $order    The column to order on
	 * @param  string $sort     The direction in which to order
	 * @param  int    $offset   The offset
	 * @param  int    $per_page The number of records to show per page
	 * @return array
	 */
	public function get_all( $order = NULL, $sort = NULL, $offset = NULL, $per_page = NULL )
	{
		//	Set defaults
		$order		= $order	? $order	: 'ea.time_sent';
		$sort		= $sort		? $sort		: 'ASC';
		$offset		= $offset	? $offset	: 0;
		$per_page	= $per_page	? $per_page	: 25;

		// --------------------------------------------------------------------------

		$this->db->select( 'ea.id, ea.ref, ea.type, ea.email_vars, ea.user_email sent_to, ue.is_verified email_verified, ue.code email_verified_code, ea.time_sent, ea.read_count, ea.link_click_count' );
		$this->db->select( 'u.first_name, u.last_name, u.id user_id, u.password user_password, u.group_id user_group, u.profile_img, u.gender, u.username' );

		$this->db->join( NAILS_DB_PREFIX . 'user u', 'u.id = ea.user_id OR u.id = ea.user_email', 'LEFT' );
		$this->db->join( NAILS_DB_PREFIX . 'user_email ue', 'ue.email = ea.user_email', 'LEFT' );

		$this->db->order_by( $order, $sort );
		$this->db->limit( $per_page, $offset );

		$_emails = $this->db->get( NAILS_DB_PREFIX . 'email_archive ea' )->result();

		// --------------------------------------------------------------------------

		//	Format emails
		foreach ( $_emails AS $email ) :

			$this->_format_email( $email );

		endforeach;

		// --------------------------------------------------------------------------

		return $_emails;
	}


	// --------------------------------------------------------------------------


	/**
	 * Count the number of records in the archive
	 * @return int
	 */
	public function count_all()
	{
		return $this->db->count_all_results( NAILS_DB_PREFIX . 'email_archive' );
	}


	// --------------------------------------------------------------------------


	/**
	 * Get en email from the archive by its ID
	 * @param  int $id The email's ID
	 * @return mixed   stdClass on success, FALSE on failure
	 */
	public function get_by_id( $id )
	{
		$this->db->where( 'ea.id', $id );
		$_item = $this->get_all();

		if ( ! $_item ) :

			return FALSE;

		endif;

		// --------------------------------------------------------------------------

		return $_item[0];
	}


	// --------------------------------------------------------------------------


	/**
	 * Gets items from the archive by it's reference
	 *
	 * @access	public
	 * @param	string	$ref	The reference of the item to get
	 * @return	array
	 **/

	/**
	 * Get an email from the archive by its reference
	 * @param  string  $ref  The email's reference
	 * @param  string  $guid The email's GUID
	 * @param  string  $hash The email's hash
	 * @return mixed         stdClass on success, FALSE on failure
	 */
	public function get_by_ref( $ref, $guid = FALSE, $hash = FALSE )
	{
		//	If guid and hash === FALSE then by-pass the check
		if ( $guid !== FALSE && $hash !== FALSE ) :

			//	Check hash
			$_check = md5( $guid . APP_PRIVATE_KEY . $ref );

			if ( $_check !== $hash ) :

				return 'BAD_HASH';

			endif;

		endif;
		// --------------------------------------------------------------------------

		$this->db->where( 'ea.ref', $ref );
		$_item = $this->get_all();

		if ( ! $_item ) :

			return FALSE;

		endif;

		return $_item[0];
	}


	// --------------------------------------------------------------------------


	/**
	 * Adds an attachment to an email
	 * @param string $file     The file's path
	 * @param string $filename The filename ot give the attachment
	 */
	private function _add_attachment( $file, $filename = NULL )
	{
		if ( ! file_exists( $file ) ) :

			return FALSE;

		endif;

		if ( ! $this->ci->email->attach( $file, 'attachment', $filename ) ) :

			return FALSE;

		else :

			return TRUE;

		endif;
	}


	// --------------------------------------------------------------------------


	/**
	 * Generates a unique reference for an email, optionally exclude strings
	 * @param  array  $exclude Strings to exclude from the reference
	 * @return string
	 */
	private function _generate_reference( $exclude = array() )
	{
		do
		{
			$_ref_ok = FALSE;
			do
			{
				$ref = random_string( 'alnum', 10 );
				if ( array_search( $ref, $exclude ) === FALSE ) :

					$_ref_ok = TRUE;

				endif;

			} while( ! $_ref_ok );

			$this->db->where( 'ref', $ref );
			$_query = $this->db->get( NAILS_DB_PREFIX . 'email_archive' );

		} while( $_query->num_rows() );

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
	private function _debugger( $input, $body, $plaintext, $recent_errors )
	{
		//	Debug mode, output data and don't actually send

		//	Remove the reference to CI; takes up a ton'na space
		if ( isset( $input->data['ci'] ) ) :

			$input->data['ci'] = '**REFERENCE TO CODEIGNITER INSTANCE**';

		endif;

		// --------------------------------------------------------------------------

		//	Input variables
		echo '<pre>';

		//	Who's the email going to?
		echo '<strong>Sending to:</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";
		echo 'email: ' . $input->to->email . "\n";
		echo 'first: ' . $input->to->first . "\n";
		echo 'last:  ' . $input->to->last . "\n";

		//	Who's the email being sent from?
		echo "\n\n" . '<strong>Sending from:</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";
		echo 'name:	' . $input->from->name . "\n";
		echo 'email:	' . $input->from->email . "\n";

		//	Input data (system & supplied)
		echo "\n\n" . '<strong>Input variables (supplied + system):</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";
		print_r( $input->data );

		//	Template
		echo "\n\n" . '<strong>Email body:</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";
		echo 'Subject:	' . $input->subject . "\n";
		echo 'template:	' . $input->template . "\n";

		if ( $recent_errors ) :

			echo "\n\n" . '<strong>Template Errors (' . count( $recent_errors ) . '):</strong>' . "\n";
			echo '-----------------------------------------------------------------' . "\n";

			foreach ( $recent_errors AS $error ) :

				echo 'Severity: ' . $error->severity . "\n";
				echo 'Mesage: ' . $error->message . "\n";
				echo 'Filepath: ' . $error->filepath . "\n";
				echo 'Line: ' . $error->line . "\n\n";

			endforeach;

		endif;

		echo "\n\n" . '<strong>Rendered HTML:</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";

		$_rendered_body = str_replace( '"', '\\"', $body );
		$_rendered_body = str_replace( array("\r\n", "\r"), "\n", $_rendered_body );
		$_lines = explode("\n", $_rendered_body);
		$_new_lines = array();

		foreach ( $_lines AS $line ) :

		    if ( ! empty( $line ) ) :

		        $_new_lines[] = $line;

		       endif;

		endforeach;

		$_rendered_body = implode( $_new_lines );

		echo '<iframe width="100%" height="900" src="" id="renderframe"></iframe>' ."\n";
		echo '<script type="text/javascript">' . "\n";
		echo 'var _body = "' . $_rendered_body. '";' . "\n";
		echo 'document.getElementById(\'renderframe\').src = "data:text/html;charset=utf-8," + escape(_body);' . "\n";
		echo '</script>' . "\n";

		echo "\n\n" . '<strong>HTML:</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";
		echo htmlentities( $body ) ."\n";

		echo "\n\n" . '<strong>Plain Text:</strong>' . "\n";
		echo '-----------------------------------------------------------------' . "\n";
		echo '</pre>' . nl2br( $plaintext ) . "\n";

		exit( 0 );
	}


	// --------------------------------------------------------------------------


	/**
	 * Increments an email's open count and adds a tracking note
	 * @param  string $ref  The email's reference
	 * @param  string $guid The email's GUID
	 * @param  string $hash The email's hash
	 * @return boolean
	 */
	public function track_open( $ref, $guid, $hash )
	{
		$_email = $this->get_by_ref( $ref, $guid, $hash );

		if ( $_email && $_email != 'BAD_HASH' ) :

			//	Update the read count and a add a track data point
			$this->db->set( 'read_count', 'read_count+1', FALSE );
			$this->db->where( 'id', $_email->id );
			$this->db->update( NAILS_DB_PREFIX . 'email_archive' );

			$this->db->set( 'created', 'NOW()', FALSE );
			$this->db->set( 'email_id', $_email->id );

			if ( active_user( 'id' ) ) :

				$this->db->set( 'user_id', active_user( 'id' ) );

			endif;

			$this->db->insert( NAILS_DB_PREFIX . 'email_archive_track_open' );

			return TRUE;

		endif;

		return FALSE;
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
	public function track_link( $ref, $guid, $hash, $link_id )
	{
		$_email = $this->get_by_ref( $ref, $guid, $hash );

		if ( $_email && $_email != 'BAD_HASH' ) :

			//	Get the link which was clicked
			$this->db->select( 'url' );
			$this->db->where( 'email_id', $_email->id );
			$this->db->where( 'id', $link_id );
			$_link = $this->db->get( NAILS_DB_PREFIX . 'email_archive_link' )->row();

			if ( $_link ) :

				//	Update the read count and a add a track data point
				$this->db->set( 'link_click_count', 'link_click_count+1', FALSE );
				$this->db->where( 'id', $_email->id );
				$this->db->update( NAILS_DB_PREFIX . 'email_archive' );

				//	Add a link trackback
				$this->db->set( 'created', 'NOW()', FALSE );
				$this->db->set( 'email_id', $_email->id );
				$this->db->set( 'link_id', $link_id );

				if ( active_user( 'id' ) ) :

					$this->db->set( 'user_id', active_user( 'id' ) );

				endif;

				$this->db->insert( NAILS_DB_PREFIX . 'email_archive_track_link' );

				//	Return the URL to go to
				return $_link->url;

			else :

				return 'BAD_LINK';

			endif;

		endif;

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
    private function _parse_links($body, $emailId, $emailRef, $isHtml = true, $needsVerified = false)
    {
        //    Set the class variables for the ID and ref (need those in the callbacks)
        $this->_generate_tracking_email_id          = $emailId;
        $this->_generate_tracking_email_ref         = $emailRef;
        $this->_generate_tracking_needs_verified    = $needsVerified;

        // --------------------------------------------------------------------------

        if ($isHtml) {

            $pattern    = '/<a .*?(href="(https?.*?)").*?>(.*?)<\/a>/';
            $body       = preg_replace_callback($pattern, array($this, '__process_link_html'), $body);

        } else {

            $pattern    = '/(https?:\/\/)([\da-z\.-]+)\.([a-z\.]{2,6})([\/\w \.-]*)*\/?/';
            $body       = preg_replace_callback($pattern, array($this, '__process_link_url'), $body);

        }

        // --------------------------------------------------------------------------

        //    And null these again, so nothing gets confused
        $this->_generate_tracking_email_id          = null;
        $this->_generate_tracking_email_ref         = null;
        $this->_generate_tracking_needs_verified    = null;

        // --------------------------------------------------------------------------

        return $body;
    }


	// --------------------------------------------------------------------------


	/**
	 * Processes a link found by _parse_links()
	 * @param  array $link The link elements
	 * @return string
	 */
	private function __process_link_html( $link )
	{
		$_html	= ! empty( $link[0] ) ? $link[0] : '';
		$_href	= ! empty( $link[1] ) ? $link[1] : '';
		$_url	= ! empty( $link[2] ) ? $link[2] : '';
		$_title	= isset( $link[3] ) && strip_tags( $link[3] ) ? strip_tags( $link[3] ) : $_url;

		// --------------------------------------------------------------------------

		/**
		 * Only process if there's at least the HTML tag and a detected URL
		 * otherwise it's not worth it/possible to accurately replace the tag
		 */

		if ( $_html && $_url ) :

			$_html = $this->__process_link_generate( $_html, $_url, $_title, TRUE );

		endif;

		return $_html;
	}


	// --------------------------------------------------------------------------


	/**
	 * Process the URL of a link found by __process_link_html()
	 * @param  array $url The URL elements
	 * @return string
	 */
	private function __process_link_url( $url )
	{
		$_html	= ! empty( $url[0] ) ? $url[0] : '';
		$_url	= $_html;
		$_title	= $_html;

		// --------------------------------------------------------------------------

		//	Only process if theres a URL to process
		if ( $_html && $_url ) :

			$_html = $this->__process_link_generate( $_html, $_url, $_title, FALSE );

		endif;

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
	private function __process_link_generate( $html, $url, $title, $is_html )
	{
		/**
		 * Generate a tracking URL for this link
		 * Firstly, check this URL hasn't been processed already (for this email)
		 */

		if ( isset( $this->_track_link_cache[md5( $url )] ) ) :

			$_tracking_url = $this->_track_link_cache[md5( $url )];

			//	Replace the URL	and return the new tag
			$html = str_replace( $url, $_tracking_url, $html );

		else :

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

			if ( $this->_generate_tracking_needs_verified ) :

				//	Make sure we're not applying this to an activation URL
				if ( ! preg_match( '#email/verify/[0-9]*?/(.*?)#', $url ) ) :

					$_user_id	= $this->_generate_tracking_needs_verified['id'];
					$_code		= $this->_generate_tracking_needs_verified['code'];
					$_return	= urlencode( $url );

					$_url = site_url( 'email/verify/' . $_user_id . '/' . $_code . '?return_to=' . $_return );

				else :

					$_url = $url;

				endif;

			else :

				$_url = $url;

			endif;

			$this->db->set( 'email_id', $this->_generate_tracking_email_id );
			$this->db->set( 'url', $_url );
			$this->db->set( 'title', $title );
			$this->db->set( 'created', 'NOW()', FALSE );
			$this->db->set( 'is_html', $is_html );
			$this->db->insert( NAILS_DB_PREFIX . 'email_archive_link' );

			$_id = $this->db->insert_id();

			if ( $_id ) :

				$_time			= time();
				$_tracking_url	= site_url( 'email/tracker/link/' . $this->_generate_tracking_email_ref . '/' . $_time . '/' . md5( $_time . APP_PRIVATE_KEY . $this->_generate_tracking_email_ref ). '/' . $_id );

				$this->_track_link_cache[md5( $url )] = $_tracking_url;

				// --------------------------------------------------------------------------

				//	Replace the URL	and return the new tag
				$html = str_replace( $url, $_tracking_url, $html );

			endif;

		endif;

		return $html;
	}


	// --------------------------------------------------------------------------


	/**
	 * Format an email object
	 * @param  object $email The raw email object
	 * @return void
	 */
	protected function _format_email( &$email )
	{
		$email->email_vars	= unserialize( $email->email_vars );
		$email->type		= ! empty( $this->_email_type[$email->type] ) ? $this->_email_type[$email->type] : NULL;

		if ( empty( $email->type ) ) :

			show_fatal_error( 'Invalid Email Type', 'Email with ID #' . $email->id . ' has an invalid email type.' );

		endif;

		// --------------------------------------------------------------------------

		/**
		 * If a subject is defined in the variables use that, if not check to see if one was
		 * defined in the template; if not, fall back to a default subject
		 */

		if ( ! empty( $email->email_vars['email_subject'] ) ) :

			$email->subject = $email->email_vars['email_subject'];

		elseif ( ! empty( $email->type->default_subject ) ) :

			$email->subject = $email->type->default_subject;

		else :

			$email->subject = 'An E-mail from ' . APP_NAME;

		endif;

		// --------------------------------------------------------------------------

		//	Template overrides
		if ( ! empty( $email->email_vars['template_header'] ) ) :

			$email->type->template_body = $email->email_vars['template_header'];

		endif;

		if ( ! empty( $email->email_vars['template_body'] ) ) :

			$email->type->template_body = $email->email_vars['template_body'];

		endif;

		if ( ! empty( $email->email_vars['template_footer'] ) ) :

			$email->type->template_body = $email->email_vars['template_footer'];

		endif;

		// --------------------------------------------------------------------------

		//	Sent to
		$email->user 				= new stdClass();
		$email->user->id			= $email->user_id;
		$email->user->group_id		= $email->user_group;
		$email->user->email			= $email->sent_to;
		$email->user->username		= $email->username;
		$email->user->password		= $email->user_password;
		$email->user->first_name	= $email->first_name;
		$email->user->last_name		= $email->last_name;
		$email->user->profile_img	= $email->profile_img;
		$email->user->gender		= $email->gender;


		unset( $email->user_id );
		unset( $email->sent_to );
		unset( $email->username );
		unset( $email->first_name );
		unset( $email->last_name );
		unset( $email->profile_img );
		unset( $email->gender );
		unset( $email->user_group );
		unset( $email->user_password );
	}
}

/* End of file Emailer.php */
/* Location: ./module-email/email/libraries/Emailer.php */