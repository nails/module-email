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
        $navGroup->addMethod('Email');

        return $navGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Manage Email settings
     * @return void
     */
    public function email()
    {
        //  Set method info
        $this->data['page']->title = 'Email';

        // --------------------------------------------------------------------------

        //  Process POST
        if ($this->input->post()) {

            $method = $this->input->post('update');
            if (method_exists($this, '_email_update_' . $method)) {

                $this->{'_email_update_' . $method}();

            } else {

                $this->data['error'] = '<strong>Sorry,</strong> I can\'t determine what type of update you are trying to perform.';
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

        $this->load->view('structure/header', $this->data);
        $this->load->view('admin/settings/email', $this->data);
        $this->load->view('structure/footer', $this->data);
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

            $this->data['success'] = '<strong>Success!</strong> General email settings have been saved.';

        } else {

            $this->data['error'] = '<strong>Sorry,</strong> there was a problem saving settings.';
        }
    }
}
