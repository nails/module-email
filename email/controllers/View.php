<?php

/**
 * This class allows users to view an email in the browser
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Email\Controller\Base;
use Nails\Factory;

class View extends Base
{
    /**
     * Handle view online requests
     */
    public function index()
    {
        $oUri     = Factory::service('Uri');
        $oEmailer = Factory::service('Emailer', 'nails/module-email');
        $sRef     = $oUri->segment(3);
        $sGuid    = $oUri->segment(4);
        $sHash    = $oUri->segment(5);

        // --------------------------------------------------------------------------

        //  Fetch the email
        if (is_numeric($sRef)) {
            $oEmail = $oEmailer->getById($sRef);
        } else {
            $oEmail = $oEmailer->getByRef($sRef);
        }

        if (!$oEmail || !$oEmailer->validateHash($oEmail->ref, $sGuid, $sHash)) {
            show_404();
        }

//        throw new \Exception();

        if (\Nails\Environment::is(Environment::ENV_DEV)) {

            $oAsset = Factory::service('Asset');
            $oAsset->load('debugger.css', 'nails/module-email');

            Factory::service('View')
                   ->setData([
                       'oEmail' => $oEmail,
                   ])
                   ->load([
                       'structure/header/blank',
                       'email/view',
                       'structure/footer/blank',
                   ]);

        } else {
            $oOutput = Factory::service('Output');
            $oOutput->set_output($oEmail->body->html);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Map all requests to index
     */
    public function _remap()
    {
        $this->index();
    }
}
