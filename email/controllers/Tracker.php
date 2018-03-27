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
        $oUri     = Factory::service('Uri');
        $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');

        $sRef  = $oUri->segment(3);
        $sGuid = $oUri->segment(4);
        $sHash = $oUri->segment(5);

        // --------------------------------------------------------------------------

        if (!$sRef || !$oEmailer->validateHash($sRef, $sGuid, $sHash)) {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Fetch the email
        $oEmailer->trackOpen($sRef);

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
        $oUri     = Factory::service('Uri');
        $oEmailer = Factory::service('Emailer', 'nailsapp/module-email');

        $sRef    = $oUri->segment(4);
        $sGuid   = $oUri->segment(5);
        $sHash   = $oUri->segment(6);
        $sLinkId = $oUri->segment(7);

        // --------------------------------------------------------------------------

        if (!$sRef || !$oEmailer->validateHash($sRef, $sGuid, $sHash)) {
            show_404();
        }

        // --------------------------------------------------------------------------

        $sUrl = $oEmailer->trackLink($sRef, $sLinkId);
        if ($sUrl === false) {
            show_404();
        }

        redirect($sUrl);
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
