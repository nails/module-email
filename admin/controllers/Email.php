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

        $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = lang('email_index_title');

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $prefix    = $oEmailer->getTableAlias();
        $page      = $this->input->get('page')      ? $this->input->get('page')      : 0;
        $perPage   = $this->input->get('perPage')   ? $this->input->get('perPage')   : 50;
        $sortOn    = $this->input->get('sortOn')    ? $this->input->get('sortOn')    : $prefix . '.sent';
        $sortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $keywords  = $this->input->get('keywords')  ? $this->input->get('keywords')  : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $sortColumns = array(
            $prefix . '.sent'   => 'Sent Date'
        );

        // --------------------------------------------------------------------------

        $aTypeOptions = array('All email types');
        $aEmailTypes  = $oEmailer->getTypes();
        foreach ($aEmailTypes as $oType) {
            $aTypeOptions[$oType->slug] = $oType->name;
        }

        $cbFilters = array();
        $ddFilters = array(
            Helper::searchFilterObject(
                $prefix . '.type',
                'Type',
                $aTypeOptions
            )
        );

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

        //  Get the items for the page
        $totalRows            = $oEmailer->countAll($data);
        $this->data['emails'] = $oEmailer->getAll($page, $perPage, $data);

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

        $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');

        // --------------------------------------------------------------------------

        $emailId = $this->uri->segment(5);
        $return  = $this->input->get('return') ? $this->input->get('return') : 'admin/email/index';

        if ($oEmailer->resend($emailId)) {

            $status  = 'success';
            $message = 'Message was resent successfully.';

        } else {

            $status  = 'error';
            $message = 'Message failed to resend. ' . $oEmailer->lastError();
        }

        $oSession = Factory::service('Session', 'nailsapp/module-auth');
        $oSession->Set_flashdata($status, $message);
        redirect($return);
    }
}
