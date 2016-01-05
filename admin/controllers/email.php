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

use Nails\Factory;
use Nails\Admin\Helper;
use Nails\Email\Controller\BaseAdmin;

class Email extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nailsapp/module-admin');
        $oNavGroup->setLabel('Email');
        $oNavGroup->setIcon('fa-paper-plane-o');

        if (userHasPermission('admin:email:email:browse')) {

            $oNavGroup->addAction('Message Archive');
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
        $permissions = parent::permissions();

        $permissions['browse'] = 'Can browse email archive';
        $permissions['resend'] = 'Can resend email';

        return $permissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load('admin_email');
    }

    // --------------------------------------------------------------------------

    /**
     * Browse the email archive
     * @return void
     */
    /**
     * Browse posts
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:email:email:browse')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = lang('email_index_title');

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : 'ea.sent';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            'ea.sent'   => 'Sent Date'
        );

        // --------------------------------------------------------------------------

        $oTypeOptions = array('Choose Type');
        $aEmailTypes  = $this->emailer->getTypes();
        foreach ($aEmailTypes as $oType) {
            $oTypeOptions[$oType->slug] = $oType->name;
        }

        $cbFilters = array();
        $ddFilters = array('type' => Helper::searchFilterObject('', 'Type', $oTypeOptions));

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $data = array(
            'sort' => array(
                array($sortOn, $sortOrder)
            ),
            'keywords'  => $keywords,
            'ddFilters' => $ddFilters
        );

        // --------------------------------------------------------------------------

        /**
         * Determine if we're restricting to a certain type
         * @todo find a better, consolidated way of doing this
         *
         * Due to the way the search component works, we need to "listen" to the $_GET
         * array by hand. Each filter above will be indexed in either ddF (DropDownFilter)
         * or cbF (CheckBoxFilter). For ddF values the value at the index is the
         * selected option.
         */

        if (!empty($_GET['ddF']['type'])) {

            $sType = Helper::searchFilterGetValueAtKey(
                $ddFilters['type'],
                $_GET['ddF']['type']
            );

            $data['type'] = $sType;
        }

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows            = $this->emailer->countAll($data);
        $this->data['emails'] = $this->emailer->getAll($page, $perPage, $data);

        //  Set Search and Pagination objects for the view
        $this->data['search'] = Helper::searchObject(
            true,
            $sortColumns,
            $sortOn,
            $sortOrder,
            $perPage,
            $keywords,
            $cbFilters,
            $ddFilters
        );
        $this->data['pagination'] = Helper::paginationObject($page, $perPage, $totalRows);

        // --------------------------------------------------------------------------

        Helper::loadView('index');
    }

    // --------------------------------------------------------------------------

    /**
     * Resent an email
     * @return void
     */
    public function resend()
    {
        if (!userHasPermission('admin:email:email:resend')) {

            unauthorised();
        }

        // --------------------------------------------------------------------------

        $emailId = $this->uri->segment(5);
        $return  = $this->input->get('return') ? $this->input->get('return') : 'admin/email/index';

        if ($this->emailer->resend($emailId)) {

            $status  = 'success';
            $message = 'Message was resent successfully.';

        } else {

            $status  = 'error';
            $message = 'Message failed to resend. ' . $this->emailer->lastError();
        }

        $this->session->Set_flashdata($status, $message);
        redirect($return);
    }
}
