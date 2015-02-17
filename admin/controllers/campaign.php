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

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $permissions = parent::permissions();

        $permissions['manage'] = 'Can manage campaigns';
        $permissions['create']  = 'Can create draft campaigns';
        $permissions['send']    = 'Can send campaigns';
        $permissions['delete']  = 'Can delete campaigns';

        return $permissions;
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
        if (!userHasPermission('admin.email:0.can_manage_campaigns')) {

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
