<?php

namespace Nails\Email\Admin;

use Nails\Email\Resource;
use Nails\Email\Model;

/**
 * Class Helper
 *
 * @package Nails\Email\Admin
 */
class Helper
{
    /**
     * Renders a status cell for an email
     *
     * @param Resource\Email|\stdClass $oEmail             The email object
     * @param bool                     $bIncludeCellMarkup Whether to include the surrounding cell markup
     *
     * @return string
     */
    public static function emailStatusCell(object $oEmail, bool $bIncludeCellMarkup = true): string
    {
        switch ($oEmail->status) {
            case Model\Email::STATUS_PENDING:
                $sCellStatus  = 'info';
                $sCellText    = 'Pending';
                $sCellSubText = '';
                $sIcon        = 'fa-clock';
                break;

            case Model\Email::STATUS_QUEUED:
                $sCellStatus  = 'info';
                $sCellText    = 'Queued';
                $sCellSubText = '';
                $sIcon        = 'fa-clock';
                break;

            case Model\Email::STATUS_SENDING:
                $sCellStatus  = 'info';
                $sCellText    = 'Sending';
                $sCellSubText = '';
                $sIcon        = 'fa-paper-plane';
                break;

            case Model\Email::STATUS_SENT:
                $sCellStatus  = 'success';
                $sCellText    = 'Sent';
                $sCellSubText = '';
                $sIcon        = 'fa-check-circle';
                break;

            case Model\Email::STATUS_BOUNCED:
                $sCellStatus  = 'warning';
                $sCellText    = 'Bounced';
                $sCellSubText = '';
                $sIcon        = 'fa-table-tennis';
                break;

            case Model\Email::STATUS_OPENED:
                $sCellStatus  = 'success';
                $sCellText    = 'Opened';
                $sCellSubText = '';
                $sIcon        = 'fa-envelope-open-text';
                break;

            case Model\Email::STATUS_REJECTED:
                $sCellStatus  = 'danger';
                $sCellText    = 'Rejected';
                $sCellSubText = $oEmail->fail_reason;
                $sIcon        = 'fa-times-circle';
                break;

            case Model\Email::STATUS_DELAYED:
                $sCellStatus  = 'warning';
                $sCellText    = 'Delayed';
                $sCellSubText = '';
                $sIcon        = 'fa-clock';
                break;

            case Model\Email::STATUS_SOFT_BOUNCED:
                $sCellStatus  = 'warning';
                $sCellText    = 'Bounced (Soft)';
                $sCellSubText = '';
                $sIcon        = 'fa-table-tennis';
                break;

            case Model\Email::STATUS_MARKED_AS_SPAM:
                $sCellStatus  = 'warning';
                $sCellText    = 'Marked as Spam';
                $sCellSubText = '';
                $sIcon        = 'fa-trash';
                break;

            case Model\Email::STATUS_CLICKED:
                $sCellStatus  = 'success';
                $sCellText    = 'Clicked';
                $sCellSubText = '';
                $sIcon        = 'fa-envelope-open-text';
                break;

            case Model\Email::STATUS_FAILED:
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

        $sCellOpen    = $bIncludeCellMarkup ? '<td class="text-center ' . $sCellStatus . '">' : '';
        $sCellClose   = $bIncludeCellMarkup ? '</td>' : '';
        $sCellSubText = !empty($sCellSubText) ? '<small>' . $sCellSubText . '</small>' : '';

        if (!empty($sIcon)) {

            $sOut = <<<EOT
            $sCellOpen
                <span class="hint--bottom" aria-label="$sCellText">
                    <b class="fa fa-lg $sIcon"></b>
                </span>
            $sCellSubText
            $sCellClose
            EOT;

        } else {

            $sOut = <<<EOT
            $sCellOpen
            $sCellText
            $sCellSubText
            $sCellClose
            EOT;
        }

        return $sOut;
    }
}
