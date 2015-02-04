<div class="group-email archive">
    <p>
        <?=lang('email_index_intro')?>
    </p>
    <hr />
    <?php

        // @todo: Add search facilities
        echo \Nails\Admin\Helper::loadPagination($emails->pagination->total_results);

    ?>
    <table>
        <thead>
            <tr>
                <th class="id"><?=lang('email_index_thead_id')?></th>
                <th class="ref"><?=lang('email_index_thead_ref')?></th>
                <th class="user"><?=lang('email_index_thead_to')?></th>
                <th class="sent"><?=lang('email_index_thead_sent')?></th>
                <th class="type"><?=lang('email_index_thead_type')?>
                <th class="status"><?=lang('email_index_thead_status')?></th>
                <th class="reads"><?=lang('email_index_thead_reads')?></th>
                <th class="clicks"><?=lang('email_index_thead_clicks')?></th>
                <th class="actions"><?=lang('email_index_thead_actions')?></th>
            </tr>
        </thead>
        <tbody>
            <?php

            if ($emails->data) {

                foreach ($emails->data as $email) {

                    ?>
                    <tr>
                        <td class="id"><?=number_format($email->id)?></td>
                        <td class="ref"><?=$email->ref?></td>
                        <?php

                            echo \Nails\Admin\Helper::loadUserCell($email->user);
                            echo \Nails\Admin\Helper::loadDatetimeCell($email->sent);

                        ?>
                        <td class="type">
                            <?=$email->type->name?>
                            <small><?=lang('email_index_subject', $email->subject)?></small>
                        </td>
                        <?php


                            switch ($email->status) {

                                case 'SENT' :

                                    $rowStatus = 'success';
                                    $rowText   = 'Sent';
                                    $icon      = 'fa-check-circle';
                                    break;

                                case 'BOUNCED' :

                                    $rowStatus = 'error';
                                    $rowText   = 'Bounced';
                                    $icon      = 'fa-times-circle';
                                    break;

                                case 'OPENED' :

                                    $rowStatus = 'success';
                                    $rowText   = 'Opened';
                                    $icon      = 'fa-check-circle';
                                    break;

                                case 'REJECTED' :

                                    $rowStatus = 'error';
                                    $rowText   = 'Rejected';
                                    $icon      = 'fa-times-circle';
                                    break;

                                case 'DELAYED' :

                                    $rowStatus = 'message';
                                    $rowText   = 'Delayed';
                                    $icon      = 'fa-warning';
                                    break;

                                case 'SOFT_BOUNCED' :

                                    $rowStatus = 'message';
                                    $rowText   = 'Bounced (Soft)';
                                    $icon      = 'fa-warning';
                                    break;

                                case 'MARKED_AS_SPAM' :

                                    $rowStatus = 'message';
                                    $rowText   = 'Marked as Spam';
                                    $icon      = 'fa-warning';
                                    break;

                                case 'CLICKED' :

                                    $rowStatus = 'success';
                                    $rowText   = 'Clicked';
                                    $icon      = 'fa-check-circle';
                                    break;

                                case 'FAILED' :

                                    $rowStatus = 'error';
                                    $rowText   = 'Failed';
                                    $icon      = 'fa-times-circle';
                                    break;

                                default :

                                    $rowStatus = '';
                                    $rowText   = ucfirst(strtolower(str_replace('_', ' ', $email->status)));
                                    $icon      = '';
                                    break;
                            }

                            echo '<td class="status ' . $rowStatus . '">';
                                echo !empty($icon) ? '<b class="fa fa-lg ' . $icon . '"></b>': '';
                                echo !empty($rowText) ? $rowText : '';
                            echo '</td>';

                        ?>
                        <td class="reads"><?=$email->read_count?></td>
                        <td class="clicks"><?=$email->link_click_count?></td>
                        <td class="actions">
                        <?php

                            echo anchor(site_url('email/view_online/' . $email->ref, isPageSecure()), lang('action_preview'), 'class="awesome small fancybox fancybox.iframe" target="_blank"');

                            if (!userHasPermission('admin.email:0.can_resend')) {

                                $return = uri_string();
                                if ($this->input->server('QUERY_STRING')) {

                                    $return .= '?' . $this->input->server('QUERY_STRING');
                                }
                                $return = urlencode($return);
                                echo anchor('admin/email/resend/' . $email->id . '?return=' . $return, 'Resend', 'class="awesome small green"');
                            }

                        ?>
                        </td>
                    </tr>
                    <?php

                }

            } else {

                ?>
                <tr>
                    <td class="no-data" colspan="9"><?=lang('email_index_noemail')?></td>
                </tr>
                <?php

            }

            ?>
        </tbody>
    </table>
    <?php

        echo \Nails\Admin\Helper::loadPagination($emails->pagination->total_results)

    ?>
</div>