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

use Nails\Common\Service\Asset;
use Nails\Common\Service\HttpCodes;
use Nails\Common\Service\Output;
use Nails\Common\Service\Uri;
use Nails\Email\Constants;
use Nails\Email\Controller\Base;
use Nails\Email\Service\Emailer;
use Nails\Environment;
use Nails\Factory;

/**
 * Class View
 */
class View extends Base
{
    /**
     * Handle view online requests
     */
    public function index()
    {
        /** @var Uri $oUri */
        $oUri = Factory::service('Uri');
        /** @var Emailer $oEmailer */
        $oEmailer = Factory::service('Emailer', Constants::MODULE_SLUG);
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

            /**
             * Using this to generate a JSON 404 as the standard show404() will attempt to
             * render the module's header/footer and it looks like a btoken email template.
             */

            /** @var HttpCodes $oHttpCodes */
            $oHttpCodes = Factory::service('HttpCodes');
            /** @var Output $oOutput */
            $oOutput = Factory::service('Output');

            $oOutput
                ->setStatusHeader($oHttpCodes::STATUS_NOT_FOUND)
                ->setContentType('application/json')
                ->setOutput(json_encode([
                    'status' => $oHttpCodes::STATUS_NOT_FOUND,
                    'error'  => 'Failed to validate email URL',
                ]));
            return;
        }

        if (Environment::is(Environment::ENV_DEV)) {

            /** @var Asset $oAsset */
            $oAsset = Factory::service('Asset');
            $oAsset
                ->clear()
                ->load('debugger.min.css', Constants::MODULE_SLUG);

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
            /** @var Output $oOutput */
            $oOutput = Factory::service('Output');
            $oOutput->setOutput($oEmail->body->html);
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
