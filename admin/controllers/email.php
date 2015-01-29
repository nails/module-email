<?php

/**
 * This class provides Email Management functionality to Admin
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Email;

class Email extends \AdminController
{
    /**
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        $d     = parent::announce();
        $d[''] = array('Email', 'Message Archive');
        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse the email archive
     * @return void
     */
    public function index()
    {
        if (!user_has_permission('admin.email:0.can_browse_archive')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = lang('email_index_title');

        // --------------------------------------------------------------------------

        //  Fetch emails from the archive
        $offset  = $this->input->get('offset');
        $perPage = $this->input->get('per_page') ? $this->input->get('per_page') : 25;

        $this->data['emails']       = new \stdClass();
        $this->data['emails']->data = $this->emailer->get_all(null, 'DESC', $offset, $perPage);

        //  Work out pagination
        $this->data['emails']->pagination                = new \stdClass();
        $this->data['emails']->pagination->total_results = $this->emailer->count_all();

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/email/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }

    // --------------------------------------------------------------------------

    /**
     * Resent an email
     * @return void
     */
    public function resend()
    {
        if (!user_has_permission('admin.email:0.can_resend')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $emailId = $this->uri->segment(4);
        $return  = $this->input->get('return') ? $this->input->get('return') : 'admin/email/index';

        if ($this->emailer->resend($emailId)) {

            $status  = 'success';
            $message = 'Message was resent successfully.';

        } else {

            $status  = 'error';
            $message = 'Message failed to resend.';
        }

        $this->session->Set_flashdata($status, $message);
        redirect($return);
    }
}
