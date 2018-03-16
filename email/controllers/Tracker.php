<?php

/**
 * This class allows Nails to track email opens and link clicks
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Email\Controller\Base;
use Nails\Factory;

class Tracker extends Base
{
    /**
     * Track an email open.
     */
    public function track_open()
    {
        /**
         * Fetch data; return a string if not set so as not to accidentally skip the
         * hash check in getByRef();
         */

        $oUri     = Factory::service('Uri');
        $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');

        $sRef  = $oUri->segment(3, 'null');
        $sGuid = $oUri->segment(4, 'null');
        $sHash = $oUri->segment(5, 'null');

        // --------------------------------------------------------------------------

        //  Fetch the email
        $oEmailer->trackOpen($sRef, $sGuid, $sHash);

        // --------------------------------------------------------------------------

        /**
         * Render out a tiny, tiny image
         * Thanks http://probablyprogramming.com/2009/03/15/the-tiniest-gif-ever
         */

        header('Content-Type: image/gif');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');

        echo base64_decode('R0lGODlhAQABAIABAP///wAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');

        // --------------------------------------------------------------------------

        /**
         * Kill script, th, th, that's all folks. Stop the output class from hijacking
         * our headers and setting an incorrect Content-Type
         */

        exit(0);
    }

    // --------------------------------------------------------------------------

    /**
     * Track a link click and forward through
     */
    public function track_link()
    {
        /**
         * Fetch data; return a string if not set so as not to accidentally skip the
         * hash check in getByRef();
         */

        $oUri     = Factory::service('Uri');
        $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');
        $oOutput  = Factory::service('Output');
        $oInput   = Factory::service('Input');

        $sRef    = $oUri->segment(4);
        $sGuid   = $oUri->segment(5, 'null');
        $sHash   = $oUri->segment(6, 'null');
        $sLinkId = $oUri->segment(7, 'null');

        // --------------------------------------------------------------------------

        //  Check the reference is present
        if (!$sRef) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Fetch the email
        $sUrl = $oEmailer->trackLink($sRef, $sGuid, $sHash, $sLinkId);

        switch ($sUrl) {

            case 'BAD_HASH':
                $oOutput->set_content_type('application/json');
                $oOutput->set_header('Cache-Control: no-store, no-cache, must-revalidate');
                $oOutput->set_header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                $oOutput->set_header('Pragma: no-cache');
                $oOutput->set_header($oInput->server('SERVER_PROTOCOL') . ' 400 Bad Request');
                $oOutput->set_output(json_encode(['status' => 400, 'error' => 'Could not validate email.']));
                log_message('error', 'Emailer link failed with reason BAD_HASH');
                break;

            case 'BAD_LINK':
                $oOutput->set_content_type('application/json');
                $oOutput->set_header('Cache-Control: no-store, no-cache, must-revalidate');
                $oOutput->set_header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                $oOutput->set_header('Pragma: no-cache');
                $oOutput->set_header($oInput->server('SERVER_PROTOCOL') . ' 400 Bad Request');
                $oOutput->set_output(json_encode(['status' => 400, 'error' => 'Could not validate link.']));
                log_message('error', 'Emailer link failed with reason BAD_LINK');
                break;

            default:
                redirect($sUrl);
                break;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Maps requests to the correct method
     *
     * @param $sMethod
     */
    public function _remap($sMethod)
    {
        if ($sMethod == 'link') {
            $this->track_link();
        } else {
            $this->track_open();
        }
    }
}
