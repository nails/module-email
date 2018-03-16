<div class="group-email archive">
    <p>
        <?=lang('email_index_intro')?>
    </p>
    <?=adminHelper('loadSearch', $oSearch)?>
    <?=adminHelper('loadPagination', $oPagination)?>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th class="id"><?=lang('email_index_thead_id')?></th>
                    <th class="ref"><?=lang('email_index_thead_ref')?></th>
                    <th class="user"><?=lang('email_index_thead_to')?></th>
                    <th class="sent"><?=lang('email_index_thead_sent')?></th>
                    <th class="type"><?=lang('email_index_thead_type')?></th>
                    <th class="status"><?=lang('email_index_thead_status')?></th>
                    <th class="reads"><?=lang('email_index_thead_reads')?></th>
                    <th class="clicks"><?=lang('email_index_thead_clicks')?></th>
                    <th class="actions"><?=lang('email_index_thead_actions')?></th>
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
                                <small><?=lang('email_index_subject', $oEmail->subject)?></small>
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
                                    site_url($oEmail->data->url->viewOnline, isPageSecure()),
                                    lang('action_preview'),
                                    'class="btn btn-xs btn-primary fancybox" data-fancybox-type="iframe"'
                                );

                                if (userHasPermission('admin:email:email:resend')) {

                                    $sReturn = uri_string();
                                    if ($this->input->server('QUERY_STRING')) {

                                        $sReturn .= '?' . $this->input->server('QUERY_STRING');
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
                            <?=lang('email_index_noemail')?>
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
