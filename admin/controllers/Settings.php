<?php

/**
 * This class registers some handlers for email settings
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
use Nails\Factory;

class Settings extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return \stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
        $oNavGroup->setLabel('Settings');
        $oNavGroup->setIcon('fa-wrench');

        if (userHasPermission('admin:email:settings:update')) {
            $oNavGroup->addAction('Email');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     * @return array
     */
    public static function permissions()
    {
        $aPermissions = parent::permissions();

        $aPermissions['update:sender'] = 'Can update sender settings';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Email settings
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:email:settings:update:.*')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput = Factory::service('Input');
        if ($oInput->post()) {

            $aSettings = [];

            if (userHasPermission('admin:email:settings:update:sender')) {
                $aSettings['from_name']  = $oInput->post('from_name');
                $aSettings['from_email'] = $oInput->post('from_email');
            }

            if (!empty($aSettings)) {
                $oAppSettingModel = Factory::model('AppSetting');
                if ($oAppSettingModel->set($aSettings, 'nails/module-email')) {
                    $this->data['success'] = 'Email settings have been saved.';
                } else {
                    $this->data['error'] = 'There was a problem saving email settings.';
                }

            } else {
                $this->data['message'] = 'No settings to save.';
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['aSettings'] = appSetting(null, 'nails/module-email', true);

        // --------------------------------------------------------------------------

        //  Assets
        $oAsset = Factory::service('Asset');
        $oAsset->load('nails.admin.settings.min.js', 'NAILS');

        // --------------------------------------------------------------------------

        //  Set page title
        $this->data['page']->title = 'Settings &rsaquo; Email';

        // --------------------------------------------------------------------------

        //  Load views
        Helper::loadView('index');
    }
}
