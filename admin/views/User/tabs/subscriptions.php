<?php

/**
 * @var \Nails\Auth\Resource\User $user
 * @var array                     $groups
 */

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

                    $action = sprintf(
                        '<span class="js-subscribe-action btn btn-xs btn-%s" data-action="%s">%s</span>',
                        $subscribed ? 'danger' : 'success',
                        $subscribed ? 'unsubscribe' : 'subscribe',
                        $subscribed ? 'Unsubscribe' : 'Subscribe'
                    );

                } else {
                    $label  = 'Mandatory';
                    $hint   = 'User will always recive this type of email';
                    $status = 'warning';
                    $action = '';
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
                    <td class="actions"><?=$action?></td>
                </tr>
                <?php
            }
        }

        ?>
    </tbody>
</table>
