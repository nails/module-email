<?php

use Nails\Common\Service\Input;
use Nails\Config;

/** @var Input $oInput */
$oInput = \Nails\Factory::service('Input');

?>
<div class="group-settings site">
    <p>
        Configure the way the app sends email.
    </p>
    <hr/>
    <?php

    echo form_open();
    echo '<input type="hidden" name="activeTab" value="' . set_value('activeTab') . '" id="activeTab" />'

    ?>
    <ul class="tabs">
        <?php

        if (userHasPermission('admin:email:settings:update:sender')) {

            $sActive = $oInput->post('activeTab') == 'tab-sender' || !$oInput->post('activeTab') ? 'active' : '';

            ?>
            <li class="tab <?=$sActive?>">
                <a href="#" data-tab="tab-sender">
                    Sender
                </a>
            </li>
            <?php
        }

        if (userHasPermission('admin:email:settings:update:retention')) {

            $sActive = $oInput->post('activeTab') == 'tab-retention' ? 'active' : '';

            ?>
            <li class="tab <?=$sActive?>">
                <a href="#" data-tab="tab-retention">
                    Data Retention
                </a>
            </li>
            <?php
        }

        ?>
    </ul>
    <section class="tabs">
        <?php

        if (userHasPermission('admin:email:settings:update:sender')) {

            $sDisplay = $oInput->post('activeTab') === 'tab-sender' || !$oInput->post('activeTab') ? 'active' : '';

            ?>
            <div class="tab-page tab-sender <?=$sDisplay?>">
                <div class="fieldset">
                    <?php

                    echo form_field([
                        'key'         => 'from_name',
                        'label'       => 'From Name',
                        'default'     => getFromArray('from_name', $aSettings, Config::get('APP_NAME'),
                        'placeholder' => 'The name of the sender which recipients should see.',
                    ]);

                    // --------------------------------------------------------------------------

                    $aUrl     = parse_url(siteUrl());
                    $sDefault = 'nobody@' . getFromArray('host', $aUrl);

                    echo form_field([
                        'key'         => 'from_email',
                        'label'       => 'From Email',
                        'default'     => getFromArray('from_email', $aSettings, $sDefault),
                        'placeholder' => 'The email address of the sender which recipients should see.',
                        'info'        => '<strong>Note:</strong> If sending using SMTP to send email ensure this email is a valid account on the mail server. If it\'s not valid, some services will junk the email.',
                    ]);

                    ?>
                </div>
            </div>
            <?php
        }

        if (userHasPermission('admin:email:settings:update:retention')) {

            $sDisplay = $oInput->post('activeTab') === 'tab-retention' ? 'active' : '';

            ?>
            <div class="tab-page tab-retention <?=$sDisplay?>">
                <div class="fieldset">
                    <?php
                    echo form_field_number([
                        'key'     => 'retention_period',
                        'label'   => 'Retention Period',
                        'default' => getFromArray('retention_period', $aSettings),
                        'info'    => 'This number defines how long emails should be kept in the archive, set to 0 to disable archive cleanup',
                    ]);
                    ?>
                </div>
            </div>
            <?php
        }

        ?>
    </section>
    <p>
        <?=form_submit('submit', lang('action_save_changes'), 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
</div>
