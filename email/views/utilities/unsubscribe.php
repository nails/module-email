<?php
$oInput = \Nails\Factory::service('Input');
?>
<div class="nails-auth login u-center-screen">
    <div class="panel">
        <h1 class="panel__header text-center">
            <?php
            if ($oInput->get('undo')) {
                echo 'Successfully Re-subscribed';
            } else {
                echo 'Successfully Unsubscribed';
            }
            ?>
        </h1>
        <div class="panel__body">
            <?php
            if ($oInput->get('undo')) {
                ?>
                <p class="text-center">
                    We'll continue to send you this type of email.
                </p>
                <p>
                    <?=anchor('email/unsubscribe?token=' . $oInput->get('token'), 'Unsubscribe', 'class="btn btn--block"')?>
                </p>
                <?php
            } else {
                ?>
                <p class="text-center">
                    We won't send you this type of email again.
                </p>
                <p>
                    <?=anchor('email/unsubscribe?token=' . $oInput->get('token') . '&undo=1', 'Undo', 'class="btn btn--block"')?>
                </p>
                <?php
            }
            ?>
        </div>
    </div>
</div>
