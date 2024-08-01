<?php

/**
 * @var \Nails\Auth\Resource\User $user
 * @var array                     $groups
 */

use \Nails\Email\Admin\Permission;

?>
<table class="js-email-subscriptions" data-user-id="<?=$user->id?>">
    <thead>
        <tr>
            <th>Email Type</th>
            <th class="text-center" style="width:150px;">Subscribed</th>
            <th class="actions" style="width:150px;">Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php

        foreach ($groups as $provider => $types) {

            ?>
            <tr>
                <td colspan="3" style="background:#ececec;">
                    <strong>Emails provided by <code><?=$provider?></code></strong>
                </td>
            </tr>
            <?php

            /** @var \Nails\Email\Resource\Type $type */
            foreach ($types as $type) {

                if ($type->canUnsubscribe()) {

                    $subscribed = $type->isSubscribed($user);

                    $label = $subscribed
                        ? 'Subscribed'
                        : 'Unsubscribed';

                    $hint = $subscribed
                        ? 'User will receive this type of email'
                        : 'User will not received this type of email';

                    $status = $subscribed
                        ? 'success'
                        : 'danger';

                    $actions = array_filter([
                        userHasPermission(Permission\Blocks\Delete::class)
                            ? '<span class="js-subscribe-action btn btn-xs btn-success ' . ($subscribed ? 'hidden' : '') . '" data-action="subscribe">Subscribe</span>'
                            : null,
                        userHasPermission(Permission\Blocks\Create::class)
                            ? '<span class="js-subscribe-action btn btn-xs btn-danger ' . ($subscribed ? '' : 'hidden') . '" data-action="unsubscribe" style="margin-top:0;">Unsubscribe</span>'
                            : null,
                    ]);

                } else {
                    $label   = 'Mandatory';
                    $hint    = 'User will always recive this type of email';
                    $status  = 'warning';
                    $actions = [];
                }

                ?>
                <tr class="js-row" data-type="<?=$type->slug?>">
                    <td>
                        <?=$type->name?>
                        <small><?=$type->description?></small>
                    </td>
                    <td class="text-center js-status <?=$status?>">
                    <span class="js-label hint--top" aria-label="<?=$hint?>">
                        <?=$label?>
                    </span>
                    </td>
                    <td class="actions"><?=implode(PHP_EOL, $actions)?></td>
                </tr>
                <?php
            }
        }

        ?>
    </tbody>
</table>
