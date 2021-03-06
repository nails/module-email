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

use Nails\Admin\Controller\Base;
use Nails\Admin\Helper;
use Nails\Common\Service\UserFeedback;
use Nails\Email\Constants;
use Nails\Factory;

/**
 * Class Email
 *
 * @package Nails\Admin\Email
 */
class Email extends Base
{
    /**
     * Announces this controller's navGroups
     *
     * @return stdClass
     */
    public static function announce()
    {
        $oNavGroup = Factory::factory('Nav', \Nails\Admin\Constants::MODULE_SLUG);
        $oNavGroup->setLabel('Email');
        $oNavGroup->setIcon('fa-paper-plane');

        if (userHasPermission('admin:email:email:browse')) {
            $oNavGroup->addAction('Email Archive');
        }

        return $oNavGroup;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns an array of permissions which can be configured for the user
     *
     * @return array
     */
    public static function permissions(): array
    {
        $aPermissions = parent::permissions();

        $aPermissions['browse'] = 'Can browse email archive';
        $aPermissions['resend'] = 'Can resend email';

        return $aPermissions;
    }

    // --------------------------------------------------------------------------

    /**
     * Browse the email archive
     *
     * @return void
     */
    /**
     * Browse posts
     *
     * @return void
     */
    public function index()
    {
        if (!userHasPermission('admin:email:email:browse')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput   = Factory::service('Input');
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);

        // --------------------------------------------------------------------------

        //  Set method info
        $this->data['page']->title = 'Message Archive';

        // --------------------------------------------------------------------------

        //  Get pagination and search/sort variables
        $sPrefix    = $oEmailer->getTableAlias();
        $iPage      = $oInput->get('page') ? $oInput->get('page') : 0;
        $iPerPage   = $oInput->get('perPage') ? $oInput->get('perPage') : 50;
        $sSortOn    = $oInput->get('sortOn') ? $oInput->get('sortOn') : $sPrefix . '.id';
        $sSortOrder = $oInput->get('sortOrder') ? $oInput->get('sortOrder') : 'desc';
        $sKeywords  = $oInput->get('keywords') ? $oInput->get('keywords') : '';

        // --------------------------------------------------------------------------

        //  Define the sortable columns
        $aSortColumns = [
            $sPrefix . '.id'   => 'ID',
            $sPrefix . '.sent' => 'Sent Date',
        ];

        // --------------------------------------------------------------------------

        $aTypeOptions = ['All email types'];
        $aEmailTypes  = $oEmailer->getTypes();
        foreach ($aEmailTypes as $oType) {
            if (empty($oType->is_hidden)) {
                $aTypeOptions[$oType->slug] = $oType->name;
            }
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
        $totalRows             = $oEmailer->countAll($aData);
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
     *
     * @return void
     */
    public function resend()
    {
        if (!userHasPermission('admin:email:email:resend')) {
            unauthorised();
        }

        // --------------------------------------------------------------------------

        $oInput   = Factory::service('Input');
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
        /** @var UserFeedback $oUserFeedback */
        $oUserFeedback = Factory::service('UserFeedback');

        // --------------------------------------------------------------------------

        $oUri     = Factory::service('Uri');
        $iEmailId = $oUri->segment(5);
        $sReturn  = $oInput->get('return') ? $oInput->get('return') : 'admin/email/index';

        if ($oEmailer->resend($iEmailId)) {
            $oUserFeedback->success('Message was resent successfully.');
        } else {
            $oUserFeedback->error('Message failed to resend. ' . $oEmailer->lastError());
        }

        redirect($sReturn);
    }
}
