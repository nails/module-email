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

class Settings extends \AdminController
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $navGroup = new \Nails\Admin\Nav('Settings');

        if (userHasPermission('admin:email:settings:update')) {

            $navGroup->addMethod('Email');
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

        $permissions['update'] = 'Can update settings';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Email settings
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:email:settings:update')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            $method = $this->input->post('update');
            if (method_exists($this, '_email_update_' . $method)) {

                $this->{'_email_update_' . $method}();

            } else {

                $this->data['error'] = 'I can\'t determine what type of update you are trying to perform.';
            }
        }

        // --------------------------------------------------------------------------

        //  Get data
        $this->data['settings'] = app_setting(null, 'email', true);

        // --------------------------------------------------------------------------

        //  Assets
        $this->asset->load('nails.admin.email.settings.min.js', true);
        $this->asset->inline('<script>_nails_settings = new NAILS_Admin_Email_Settings();</script>');

        // --------------------------------------------------------------------------

        //  Set page title
        $this->data['page']->title = 'Settings &rsaquo; Email';

        // --------------------------------------------------------------------------

        //  Load views
        \Nails\Admin\Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Set Email settings
     * @return void
     */
    protected function _email_update_general()
    {
        //  Prepare update
        $settings               = array();
        $settings['from_name']  = $this->input->post('from_name');
        $settings['from_email'] = $this->input->post('from_email');

        // --------------------------------------------------------------------------

        if ($this->app_setting_model->set($settings, 'email')) {

            $this->data['success'] = 'General email settings have been saved.';

        } else {

            $this->data['error'] = 'There was a problem saving settings.';
        }
    }
}
