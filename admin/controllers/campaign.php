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

use Nails\Admin\Helper;
use Nails\Email\Controller\BaseAdmin;

class Campaign extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $navGroup = new \Nails\Admin\Nav('Email', 'fa-paper-plane-o');

        if (userHasPermission('admin:email:campaign:manage')) {

            $navGroup->addAction('Manage Campaigns');
        }

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
        if (!userHasPermission('admin:email:campaign:manage')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Page Title
        $this->data['page']->title = lang('email_campaign_title');

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }
}
