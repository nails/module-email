<?php

use Nails\Admin\Helper;
use Nails\Common\Service\Input;

/** @var Input $oInput */
$oInput = \Nails\Factory::service('Input');

?>
<div class="group-email archive">
    <?=Helper::loadSearch($oSearch)?>
    <?=Helper::loadPagination($oPagination)?>
    <table class="table table-striped table-hover table-bordered table-responsive">
        <thead class="table-dark">
            <tr>
                <th class="id">ID</th>
                <th class="ref">Ref</th>
                <th class="user">To</th>
                <th class="type">Type</th>
                <th class="sent">Sent</th>
                <th class="status">Status</th>
                <th class="reads">Opens</th>
                <th class="clicks">Clicks</th>
                <th class="actions">Actions</th>
            </tr>
        </thead>
        <tbody class="align-middle">
            <?php

            if ($aEmails) {

                foreach ($aEmails as $oEmail) {

                    ?>
                    <tr>
                        <td class="id"><?=number_format($oEmail->id)?></td>
                        <td class="ref"><?=$oEmail->ref?></td>
                        <?=Helper::loadUserCell($oEmail->to)?>
                        <td class="type">
                            <?=$oEmail->type->name?>
                            <small>Subject: <?=$oEmail->subject?></small>
                        </td>
                        <?=Helper::loadDatetimeCell($oEmail->sent)?>
                        <?=\Nails\Email\Admin\Helper::emailStatusCell($oEmail)?>
                        <td class="text-center reads"><?=$oEmail->read_count?></td>
                        <td class="text-center clicks"><?=$oEmail->link_click_count?></td>
                        <td class="actions">
                            <?php

                            echo anchor(
                                siteUrl($oEmail->data->url->viewOnline, \Nails\Functions::isPageSecure()),
                                lang('action_preview'),
                                'class="btn btn-xs btn-primary fancybox" data-fancybox-type="iframe"'
                            );

                            if (userHasPermission(\Nails\Email\Admin\Permission\Archive\Resend::class)) {

                                $sReturn = uri_string();
                                if ($oInput->server('QUERY_STRING')) {
                                    $sReturn .= '?' . $oInput->server('QUERY_STRING');
                                }
                                $sReturn = urlencode($sReturn);
                                echo anchor(
                                    \Nails\Email\Admin\Controller\Archive::url('resend/' . $oEmail->id . '?return=' . $sReturn),
                                    'Resend',
                                    'class="btn btn-xs btn-success"'
                                );
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
    <?=Helper::loadPagination($oPagination)?>
</div>
