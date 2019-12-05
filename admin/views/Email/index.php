<?php
$oInput = \Nails\Factory::service('Input');
?>
<div class="group-email archive">
    <p>
        This page shows you all the mail which has been sent by the system.
    </p>
    <?=adminHelper('loadSearch', $oSearch)?>
    <?=adminHelper('loadPagination', $oPagination)?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="id">ID</th>
                    <th class="ref">Ref</th>
                    <th class="user">To</th>
                    <th class="sent">Sent</th>
                    <th class="type">Type</th>
                    <th class="status">Status</th>
                    <th class="reads">Opens</th>
                    <th class="clicks">Clicks</th>
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php

                if ($aEmails) {

                    foreach ($aEmails as $oEmail) {

                        ?>
                        <tr>
                            <td class="id"><?=number_format($oEmail->id)?></td>
                            <td class="ref"><?=$oEmail->ref?></td>
                            <?=adminHelper('loadUserCell', $oEmail->to)?>
                            <?=adminHelper('loadDatetimeCell', $oEmail->sent)?>
                            <td class="type">
                                <?=$oEmail->type->name?>
                                <small>Subject: <?=$oEmail->subject?></small>
                            </td>
                            <?php

                            switch ($oEmail->status) {
                                case 'SENT':
                                    $aRowStatus = 'success';
                                    $sRowText   = 'Sent';
                                    $sIcon      = 'fa-check-circle';
                                    break;

                                case 'BOUNCED':
                                    $aRowStatus = 'error';
                                    $sRowText   = 'Bounced';
                                    $sIcon      = 'fa-times-circle';
                                    break;

                                case 'OPENED':
                                    $aRowStatus = 'success';
                                    $sRowText   = 'Opened';
                                    $sIcon      = 'fa-check-circle';
                                    break;

                                case 'REJECTED':
                                    $aRowStatus = 'error';
                                    $sRowText   = 'Rejected';
                                    $sIcon      = 'fa-times-circle';
                                    break;

                                case 'DELAYED':
                                    $aRowStatus = 'message';
                                    $sRowText   = 'Delayed';
                                    $sIcon      = 'fa-warning';
                                    break;

                                case 'SOFT_BOUNCED':
                                    $aRowStatus = 'message';
                                    $sRowText   = 'Bounced (Soft)';
                                    $sIcon      = 'fa-warning';
                                    break;

                                case 'MARKED_AS_SPAM':
                                    $aRowStatus = 'message';
                                    $sRowText   = 'Marked as Spam';
                                    $sIcon      = 'fa-warning';
                                    break;

                                case 'CLICKED':
                                    $aRowStatus = 'success';
                                    $sRowText   = 'Clicked';
                                    $sIcon      = 'fa-check-circle';
                                    break;

                                case 'FAILED':
                                    $aRowStatus = 'error';
                                    $sRowText   = 'Failed';
                                    $sIcon      = 'fa-times-circle';
                                    break;

                                default:
                                    $aRowStatus = '';
                                    $sRowText   = ucfirst(strtolower(str_replace('_', ' ', $oEmail->status)));
                                    $sIcon      = '';
                                    break;
                            }

                            echo '<td class="status ' . $aRowStatus . '">';
                            echo !empty($sIcon) ? '<b class="fa fa-lg ' . $sIcon . '"></b>' : '';
                            echo !empty($sRowText) ? $sRowText : '';
                            echo '</td>';

                            ?>
                            <td class="reads"><?=$oEmail->read_count?></td>
                            <td class="clicks"><?=$oEmail->link_click_count?></td>
                            <td class="actions">
                                <?php

                                echo anchor(
                                    siteUrl($oEmail->data->url->viewOnline, \Nails\Functions::isPageSecure()),
                                    lang('action_preview'),
                                    'class="btn btn-xs btn-primary fancybox" data-fancybox-type="iframe"'
                                );

                                if (userHasPermission('admin:email:email:resend')) {

                                    $sReturn = uri_string();
                                    if ($oInput->server('QUERY_STRING')) {
                                        $sReturn .= '?' . $oInput->server('QUERY_STRING');
                                    }
                                    $sReturn = urlencode($sReturn);
                                    echo anchor('admin/email/email/resend/' . $oEmail->id . '?return=' . $sReturn, 'Resend', 'class="btn btn-xs btn-success"');
                                }

                                ?>
                            </td>
                        </tr>
                        <?php

                    }

                } else {

                    ?>
                    <tr>
                        <td class="no-data" colspan="9">
                            No Emails Found
                        </td>
                    </tr>
                    <?php

                }

                ?>
            </tbody>
        </table>
    </div>
    <?=adminHelper('loadPagination', $oPagination)?>
</div>
