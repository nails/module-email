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
        $navGroup = new \Nails\Admin\Nav('Email');
        $navGroup->addMethod('Manage Campaigns');
        return $navGroup;
    }

    // --------------------------------------------------------------------------

    public function __construct()
    {
        parent::__construct();
        $this->lang->load('admin_email_campaign');
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
        \Nails\Admin\Helper::loadView('index');
    }
}
