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

class View_Online extends Base
{
    /**
     * Handle view online requests
     */
    public function index()
    {
        /**
         * Fetch data; return a string if not set so as not to accidentally skip the
         * hash check in getByRef();
         */

        $oUri       = Factory::service('Uri');
        $oInput     = Factory::service('Input');
        $oOutput    = Factory::service('Output');
        $oEmailer   = Factory::service('Emailer', 'nailsapp/module-email');
        $oUserModel = Factory::model('User', 'nailsapp/module-auth');
        $sRef       = $oUri->segment(3, 'null');

        if (isAdmin()) {
            $sGuid = false;
            $sHash = false;
        } else {
            $sGuid = $oUri->segment(4, 'null');
            $sHash = $oUri->segment(5, 'null');
        }

        // --------------------------------------------------------------------------

        //  Fetch the email
        if (is_numeric($sRef)) {
            $oEmail = $oEmailer->getById($sRef, $sGuid, $sHash);
        } else {
            $oEmail = $oEmailer->getByRef($sRef, $sGuid, $sHash);
        }

        if (!$oEmail || $oEmail == 'BAD_HASH') {
            show_404();
        }

        // --------------------------------------------------------------------------

        //  Load template
        if ($oInput->get('pt')) {

            $sOut = '<html><head><title>' . $oEmail->subject . '</title></head><body><pre>';
            $sOut .= $oEmail->body->text;
            $sOut .= '</pre></body></html>';

            //  Sanitise a little
            $sOut = preg_replace('/{unwrap}(.*?){\/unwrap}/', '$1', $sOut);

        } else {
            $sOut = $oEmail->body->html;
        }

        if ($oUserModel->isSuperuser() && $oInput->get('show_vars')) {

            $sVarsObj = print_r($oEmail->data, true);
            $sVars    = <<< EOF
<style type="text/css">
    body {
        margin-top: 421px;
    }
    #vars-container {
        font-size: 13px;
        font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, sans-serif;
        line-height: 1.75em;
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 400px;
        border-bottom: 1px solid #CCC;
        padding: 10px;
        background: #EFEFEF;
        overflow: auto;
    }
    #vars-container p {
        margin-top: 0;
        border-bottom: 1px solid #CCC;
        padding-bottom: 10px;
    }
    #vars-container div {
        white-space: pre;
    }
</style>
<div id="vars-container">
    <p>
        <strong>Superusers only: Email Variables</strong>
    </p>
    <div>$sVarsObj</div>
</div>

EOF;

            $sOut = preg_replace('/<body.*?>/', '$0' . $sVars, $sOut);
        }

        $oOutput->set_output($sOut);
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
