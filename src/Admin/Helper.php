<?php

namespace Nails\Email\Admin;

use Nails\Email\Resource\Email;
use Nails\Email\Service\Emailer;

/**
 * Class Helper
 *
 * @package Nails\Email\Admin
 */
class Helper
{
    public static function emailStatusCell(Email $oEmail): string
    {
        switch ($oEmail->status) {
            case Emailer::STATUS_PENDING:
                $sCellStatus  = 'info';
                $sCellText    = 'Pending';
                $sCellSubText = '';
                $sIcon        = 'fa-clock';
                break;

            case Emailer::STATUS_SENT:
                $sCellStatus  = 'success';
                $sCellText    = 'Sent';
                $sCellSubText = '';
                $sIcon        = 'fa-check-circle';
                break;

            case Emailer::STATUS_BOUNCED:
                $sCellStatus  = 'warning';
                $sCellText    = 'Bounced';
                $sCellSubText = '';
                $sIcon        = 'fa-table-tennis';
                break;

            case Emailer::STATUS_OPENED:
                $sCellStatus  = 'success';
                $sCellText    = 'Opened';
                $sCellSubText = '';
                $sIcon        = 'fa-envelope-open-text';
                break;

            case Emailer::STATUS_REJECTED:
                $sCellStatus  = 'danger';
                $sCellText    = 'Rejected';
                $sCellSubText = $oEmail->fail_reason;
                $sIcon        = 'fa-times-circle';
                break;

            case Emailer::STATUS_DELAYED:
                $sCellStatus  = 'warning';
                $sCellText    = 'Delayed';
                $sCellSubText = '';
                $sIcon        = 'fa-clock';
                break;

            case Emailer::STATUS_SOFT_BOUNCED:
                $sCellStatus  = 'warning';
                $sCellText    = 'Bounced (Soft)';
                $sCellSubText = '';
                $sIcon        = 'fa-table-tennis';
                break;

            case Emailer::STATUS_MARKED_AS_SPAM:
                $sCellStatus  = 'warning';
                $sCellText    = 'Marked as Spam';
                $sCellSubText = '';
                $sIcon        = 'fa-trash';
                break;

            case Emailer::STATUS_CLICKED:
                $sCellStatus  = 'success';
                $sCellText    = 'Clicked';
                $sCellSubText = '';
                $sIcon        = 'fa-envelope-open-text';
                break;

            case Emailer::STATUS_FAILED:
                $sCellStatus  = 'danger';
                $sCellText    = 'Failed';
                $sCellSubText = $oEmail->fail_reason;
                $sIcon        = 'fa-times-circle';
                break;

            default:
                $sCellStatus  = '';
                $sCellText    = ucfirst(strtolower(str_replace('_', ' ', $oEmail->status)));
                $sCellSubText = '';
                $sIcon        = '';
                break;
        }

        $sCellSubText = !empty($sCellSubText) ? '<small>' . $sCellSubText . '</small>' : '';

        if (!empty($sIcon)) {

            $sOut = <<<EOT
            <td class="text-center $sCellStatus">
                <span class="hint--bottom" aria-label="$sCellText">
                    <b class="fa fa-lg $sIcon"></b>
                </span>
               $sCellSubText
            </td>
            EOT;

        } else {

            $sOut = <<<EOT
            <td class="text-center $sCellStatus">
                $sCellText
                $sCellSubText
            </td>
            EOT;
        }

        return $sOut;
    }
}
