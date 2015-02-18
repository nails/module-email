<?php

//  Include _email.php; executes common functionality
require_once '_email.php';

/**
 * This class allows users to view an email in the browser
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_View_Online extends NAILS_Email_Controller
{

    /**
     * Constructor
     *
     * @access  public
     * @param   none
     * @return  void
     **/
    public function index()
    {
        /**
         * Fetch data; return a string if not set so as not to accidentally skip the
         * hash check in get_by_ref();
         */

        $ref = $this->uri->segment(3, 'null');

        if ($this->user_model->isAdmin()) {

            $guid = false;
            $hash = false;

        } else {

            $guid = $this->uri->segment(4, 'null');
            $hash = $this->uri->segment(5, 'null');
        }

        // --------------------------------------------------------------------------

        //  Fetch the email
        $email = $this->emailer->get_by_ref($ref, $guid, $hash);

        if (!$email || $email == 'BAD_HASH') {

            show_error(lang('invalid_email'));
        }

        // --------------------------------------------------------------------------

        //  Prep data
        $data                       = $email->email_vars;
        $data['ci']                 =& get_instance();
        $data['email_ref']          = $email->ref;
        $data['sent_from']          = $this->emailer->from;
        $data['email_subject']      = $email->subject;
        $data['site_url']           = site_url();
        $data['secret']             = APP_PRIVATE_KEY;
        $data['email_type']         = $email->type;
        $data['sent_to']            = new stdClass();
        $data['sent_to']->email     = $email->user->email;
        $data['sent_to']->first     = $email->user->first_name;
        $data['sent_to']->last      = $email->user->last_name;
        $data['sent_to']->id        = (int) $email->user->id;
        $data['sent_to']->username  = $email->user->username;
        $data['sent_to']->group_id  = $email->user->group_id;

        if ($email->user->id) {

            $md5Id = md5($email->user->id);
            $md5Pw = md5($email->user->password);
            $data['sent_to']->login_url = site_url('auth/login/with_hashes/' . $md5Id . '/' . $md5Pw);

        } else {

            $data['sent_to']->login_url = null;
        }

        //  Check login URLs are allowed
        $this->config->load('auth/auth');

        if (!$this->config->item('auth_enable_hashed_login')) {

            $data['sent_to']->login_url = '';
        }

        // --------------------------------------------------------------------------

        //  Load template
        if ($this->input->get('pt')) {

            $out  = '<html><head><title>' . $email->subject . '</title></head><body><pre>';
            $out .= $this->load->view($email->type->template_header . '_plaintext', $data, true);
            $out .= $this->load->view($email->type->template_body . '_plaintext', $data, true);
            $out .= $this->load->view($email->type->template_footer . '_plaintext', $data, true);
            $out .= '</pre></body></html>';

            //  Sanitise a little
            $out = preg_replace('/{unwrap}(.*?){\/unwrap}/', '$1', $out);

        } else {

            $out  = '';
            $out .= $this->load->view($email->type->template_header, $data, true);
            $out .= $this->load->view($email->type->template_body, $data, true);
            $out .= $this->load->view($email->type->template_footer, $data, true);

            if ($this->user_model->isSuperuser() && $this->input->get('show_vars')) {

                $vars  = '<div style="max-width:600px;border:1px solid #CCC;margin:10px;padding:10px;background:#EFEFEF;white-space:pre;">';
                $vars .= '<p style="margin-top:0;border-bottom:1px solid #CCC;padding-bottom:10px;"><strong>Superusers only: Email Variables</strong></p>';
                $vars .= print_r($email->email_vars, true);
                $vars .= '</div>';

                $out = preg_replace('/<body.*?>/', '$0' . $vars, $out);
            }
        }

        // --------------------------------------------------------------------------

        //  Output
        $this->output->set_output($out);
    }

    // --------------------------------------------------------------------------

    /**
     * Map all requests to index
     *
     * @access  public
     * @param   none
     * @return  void
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

if (!defined('NAILS_ALLOW_EXTENSION_VIEW_ONLINE')) {

    class View_online extends NAILS_View_online
    {
    }
}