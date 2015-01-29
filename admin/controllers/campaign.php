<?php

/**
 * This class provides Email Campaign functionality to Admin
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    AdminController
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Admin\Email;

class Campaign extends \AdminController
{
    /**
     * Announces this controllers methods
     * @return stdClass
     */
    public static function announce()
    {
        $d     = parent::announce();
        $d[''] = array('Email', 'Manage Campaigns');
        return $d;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage email campaigns
     * @return void
     */
    public function index()
    {
        if (!user_has_permission('admin.email:0.can_manage_campaigns')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = lang('email_campaign_title');

        // --------------------------------------------------------------------------

        //  Load views
        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/email/campaign/index', $this->data);
        $this->load->view('structure/footer', $this->data);
    }
}
