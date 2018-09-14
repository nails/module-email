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

use Nails\Admin\Helper;
use Nails\Email\Controller\BaseAdmin;
use Nails\Factory;

class Email extends BaseAdmin
{
    /**
     * Announces this controller's navGroups
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', 'nails/module-admin');
        $oNavGroup->setLabel('Email');
        $oNavGroup->setIcon('fa-paper-plane-o');

        if (userHasPermission('admin:email:email:browse')) {
            $oNavGroup->addAction('Email Archive');
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

        $aPermissions['browse'] = 'Can browse email archive';
        $aPermissions['resend'] = 'Can resend email';

        return $aPermissions;
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

        $oEmailer = Factory::service('Emailer', 'nails/module-email');

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Message Archive';

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $sPrefix    = $oEmailer->getTableAlias();
        $iPage      = $this->input->get('page') ? $this->input->get('page') : 0;
        $iPerPage   = $this->input->get('perPage') ? $this->input->get('perPage') : 50;
        $sSortOn    = $this->input->get('sortOn') ? $this->input->get('sortOn') : $sPrefix . '.sent';
        $sSortOrder = $this->input->get('sortOrder') ? $this->input->get('sortOrder') : 'desc';
        $sKeywords  = $this->input->get('keywords') ? $this->input->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = [
            $sPrefix . '.sent' => 'Sent Date',
        ];

        // --------------------------------------------------------------------------

        $aTypeOptions = ['All email types'];
        $aEmailTypes  = $oEmailer->getTypes();
        foreach ($aEmailTypes as $oType) {
            $aTypeOptions[$oType->slug] = $oType->name;
        }

        $aCbFilters = [];
        $aDdFilters = [
            Helper::searchFilterObject(
                $sPrefix . '.type',
                'Type',
                $aTypeOptions
            ),
        ];

        // --------------------------------------------------------------------------

        //  Define the $data variable for the queries
        $aData = [
            'sort'      => [
                [$sSortOn, $sSortOrder],
            ],
            'keywords'  => $sKeywords,
            'ddFilters' => $aDdFilters,
        ];

        // --------------------------------------------------------------------------

        //  Get the items for the page
        $totalRows            = $oEmailer->countAll($aData);
        $this->data['aEmails'] = $oEmailer->getAll($iPage, $iPerPage, $aData);

        //  Set Search and Pagination objects for the view
        $this->data['oSearch']     = Helper::searchObject(
            true,
            $aSortColumns,
            $sSortOn,
            $sSortOrder,
            $iPerPage,
            $sKeywords,
            $aCbFilters,
            $aDdFilters
        );
        $this->data['oPagination'] = Helper::paginationObject($iPage, $iPerPage, $totalRows);

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

        $oEmailer = Factory::service('Emailer', 'nails/module-email');

        // --------------------------------------------------------------------------

        $iEmailId = $this->uri->segment(5);
        $sReturn  = $this->input->get('return') ? $this->input->get('return') : 'admin/email/index';

        if ($oEmailer->resend($iEmailId)) {
            $sStatus  = 'success';
            $sMessage = 'Message was resent successfully.';
        } else {
            $sStatus  = 'error';
            $sMessage = 'Message failed to resend. ' . $oEmailer->lastError();
        }

        $oSession = Factory::service('Session', 'nails/module-auth');
        $oSession->setFlashData($sStatus, $sMessage);
        redirect($sReturn);
    }
}
