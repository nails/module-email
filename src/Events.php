<?php

/**
 * The class provides a summary of the events fired by this module
 *
 * @package    Nails
 * @subpackage module-email
 * @category   Events
 * @author     Nails Dev Team
 */

namespace Nails\Email;

use Nails\Common\Events\Base;

/**
 * Class Events
 *
 * @package Nails\Email
 */
class Events extends Base
{
    /**
     * Fired when an email open is tracked
     *
     * @param \Nails\Email\Resource\Email $oEmail The email which was opened
     */
    const EMAIL_TRACK_OPEN = 'EMAIL:TRACK:OPEN';

    /**
     * Fired when an email link is tracked
     *
     * @param \Nails\Email\Resource\Email $oEmail The link which was clicked
     */
    const EMAIL_TRACK_LINK = 'EMAIL:TRACK:LINK';
}
