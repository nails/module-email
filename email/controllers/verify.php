<?php

//  Include _email.php; executes common functionality
require_once '_email.php';

/**
 * This class allows users to verify their email address
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_Verify extends NAILS_Email_Controller
{
    /**
     * Attempt to validate the user's activation code
     *
     * @access  public
     * @param   none
     * @return  void
     **/
    public function index()
    {
        //  Define the key variables
        $id   = $this->uri->segment(3, null);
        $code = $this->uri->segment(4, null);

        // --------------------------------------------------------------------------

        //  Fetch the user
        $u = $this->user_model->getById($id);

        if ($u && $code) {

            //  User found, attempt to verify
            if ($this->user_model->email_verify($u->id, $code)) {

                //  Reward referrer (if any
                if (!empty($u->referred_by)) {

                    $this->user_model->rewardReferral($u->id, $u->referred_by);
                }

                // --------------------------------------------------------------------------

                //  Send user on their way
                if ($this->input->get('return_to')) {

                    /**
                     * Let the next page handle whether the user is logged in or not etc.
                     * Ahh, go on set a wee notice that the user's email has been verified
                     */

                    $this->session->set_flashdata('message', lang('email_verify_ok_subtle'));
                    redirect($this->input->get('return_to'));

                } elseif (!$this->user_model->isLoggedIn()) {

                    //  Set success message
                    $this->session->set_flashdata('success', lang('email_verify_ok'));

                    //  If a password change is requested, then redirect here
                    if ($u->temp_pw) {

                        //  Send user on their merry way
                        redirect('auth/reset_password/' . $u->id . '/' . md5($u->salt));

                    } else {

                        //  Nope, log in as normal
                        $this->user_model->setLoginData($u->id);
                        redirect($u->group_homepage);
                    }

                } else {

                    $this->session->set_flashdata('success', lang('email_verify_ok'));
                    redirect($u->group_homepage);
                }
            }
        }

        // --------------------------------------------------------------------------

        $this->session->set_flashdata('error', lang('email_verify_fail_error') . ' ' . $this->user_model->lastError());
        redirect('/');
    }


    // --------------------------------------------------------------------------


    /**
     *  Map the class so that index() does all the work
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
 * OVERLOADING NAILS' EMAIL MODULE
 *
 * The following block of code makes it simple to extend one of the core email
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
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION')) {

    class Verify extends NAILS_Verify
    {
    }
}
