<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
* Name:			View Online
*
* Description:	Allows users to view an email sent to them in their browser
*
*/

//	Include _email.php; executes common functionality
require_once '_email.php';

/**
 * OVERLOADING NAILS' EMAIL MODULES
 *
 * Note the name of this class; done like this to allow apps to extend this class.
 * Read full explanation at the bottom of this file.
 *
 **/

class NAILS_View_Online extends NAILS_Email_Controller
{

	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	none
	 * @return	void
	 **/
	public function index()
	{
		//	Fetch data; return a string if not set so as not to accidentally skip the
		//	hash check in get_by_ref();

		$_ref = $this->uri->segment( 3, 'NULL' );

		if ( $this->user_model->is_admin() ) :

			$_guid	= FALSE;
			$_hash	= FALSE;

		else:

			$_guid	= $this->uri->segment( 4, 'NULL' );
			$_hash	= $this->uri->segment( 5, 'NULL' );

		endif;

		// --------------------------------------------------------------------------

		//	Fetch the email
		$this->load->library( 'emailer' );

		$_email = $this->emailer->get_by_ref( $_ref, $_guid, $_hash );

		if ( ! $_email || $_email == 'BAD_HASH' ) :

			show_error( lang( 'invalid_email' ) );

		endif;

		// --------------------------------------------------------------------------

		//	Prep data
		$_data					= $_email->email_vars;

		$_data['ci']			=& get_instance();
		$_data['email_ref']		= $_email->ref;
		$_data['sent_from']		= $this->emailer->from;
		$_data['email_subject']	= $_email->subject;
		$_data['site_url']		= site_url();
		$_data['secret']		= APP_PRIVATE_KEY;
		$_data['email_type_id']	= $_email->type_id;

		$_data['sent_to']				= new stdClass();
		$_data['sent_to']->email		= $_email->user->email;
		$_data['sent_to']->first		= $_email->user->first_name;
		$_data['sent_to']->last			= $_email->user->last_name;
		$_data['sent_to']->id			= (int) $_email->user->id;
		$_data['sent_to']->username		= $_email->user->username;
		$_data['sent_to']->group_id		= $_email->user->group_id;
		$_data['sent_to']->login_url	= $_email->user->id ? site_url( 'auth/login/with_hashes/' . md5( $_email->user->id ) . '/' . md5( $_email->user->password ) ) : NULL;

		//	Check login URLs are allowed
		$this->config->load( 'auth' );

		if ( ! $this->config->item( 'auth_enable_hashed_login' ) ) :

			$_data['sent_to']->login_url = '';

		endif;

		// --------------------------------------------------------------------------

		//	Load template
		if ( $this->input->get( 'pt' ) ) :

			$_out  = '<html><head><title>' . $_email->subject . '</title></head><body><pre>';
			$_out .= $this->load->view( 'email/structure/header_plaintext',	$_data, TRUE );
			$_out .= $this->load->view( 'email/' . $_email->template_file . '_plaintext',	$_data, TRUE );
			$_out .= $this->load->view( 'email/structure/footer_plaintext',	$_data, TRUE );
			$_out .= '</pre></body></html>';

			//	Sanitise a little
			$_out = preg_replace( '/{unwrap}(.*?){\/unwrap}/', '$1', $_out );

		else :

			$_out = '';

			if ( $this->user_model->is_superuser() && $this->input->get( 'show_vars' ) ) :

				$_out .= '<div style="max-width:600px;border:1px solid #CCC;margin:10px;padding:10px;background:#EFEFEF;white-space:pre;">';
				$_out .= '<p style="margin-top:0;border-bottom:1px solid #CCC;padding-bottom:10px;"><strong>Superusers only: Email Variables</strong></p>';
				$_out .= print_r( $_email->email_vars, TRUE );
				$_out .= '</div>';

			endif;

			$_out .= $this->load->view( 'email/structure/header',			$_data, TRUE );
			$_out .= $this->load->view( 'email/' . $_email->template_file,	$_data, TRUE );
			$_out .= $this->load->view( 'email/structure/footer',			$_data, TRUE );

		endif;

		// --------------------------------------------------------------------------

		//	Output
		$this->output->set_output( $_out );
	}


	// --------------------------------------------------------------------------


	/**
	 * Map all requests to index
	 *
	 * @access	public
	 * @param	none
	 * @return	void
	 **/
	public function _remap()
	{
		$this->index();
	}

}


// --------------------------------------------------------------------------


/**
 * OVERLOADING NAILS' EMAIL MODULES
 *
 * The following block of code makes it simple to extend one of the core admin
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION_CLASSNAME
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if ( ! defined( 'NAILS_ALLOW_EXTENSION_VIEW_ONLINE' ) ) :

	class View_online extends NAILS_View_online
	{
	}

endif;


/* End of file view_online.php */
/* Location: ./application/modules/email/controllers/view_online.php */