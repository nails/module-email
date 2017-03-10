<?php

use Nails\Factory;

//  Include _email.php; executes common functionality
require_once '_email.php';

/**
 * This class allows users to view an email in the browser
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

class NAILS_View_Online extends NAILS_Email_Controller
{

    /**
     * Constructor
     *
     * @access  public
     * @param   none
     * @return  void
     **/
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
        $sRef        = $oUri->segment(3, 'null');

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

            $sOut  = '<html><head><title>' . $oEmail->subject . '</title></head><body><pre>';
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

        // --------------------------------------------------------------------------

        //  Output
        $oOutput->set_output($sOut);
    }

    // --------------------------------------------------------------------------

    /**
     * Map all requests to index
     *
     * @access  public
     * @param   none
     * @return  void
     **/
    public function _remap()
    {
        $this->index();
    }
}

// --------------------------------------------------------------------------

/**
 * OVERLOADING NAILS' EMAIL MODULES
 *
 * The following block of code makes it simple to extend one of the core admin
 * controllers. Some might argue it's a little hacky but it's a simple 'fix'
 * which negates the need to massively extend the CodeIgniter Loader class
 * even further (in all honesty I just can't face understanding the whole
 * Loader class well enough to change it 'properly').
 *
 * Here's how it works:
 *
 * CodeIgniter instantiate a class with the same name as the file, therefore
 * when we try to extend the parent class we get 'cannot redeclare class X' errors
 * and if we call our overloading class something else it will never get instantiated.
 *
 * We solve this by prefixing the main class with NAILS_ and then conditionally
 * declaring this helper class below; the helper gets instantiated et voila.
 *
 * If/when we want to extend the main class we simply define NAILS_ALLOW_EXTENSION_CLASSNAME
 * before including this PHP file and extend as normal (i.e in the same way as below);
 * the helper won't be declared so we can declare our own one, app specific.
 *
 **/

if (!defined('NAILS_ALLOW_EXTENSION_VIEW_ONLINE')) {

    class View_online extends NAILS_View_online
    {
    }
}
