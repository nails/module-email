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

            $active = $this->input->post('activeTab') == 'tab-sender' || !$this->input->post('activeTab') ? 'active' : '';

            ?>
            <li class="tab <?=$active?>">
                <a href="#" data-tab="tab-sender">Sender</a>
            </li>
            <?php
        }
        ?>
    </ul>
    <section class="tabs">
        <?php

        if (userHasPermission('admin:email:settings:update:sender')) {

            $sDisplay = $this->input->post('activeTab') == 'tab-sender' || !$this->input->post('activeTab') ? 'active' : '';

            ?>
            <div class="tab-page tab-sender <?=$sDisplay?>">
                <div class="fieldset">
                    <?php

                    echo form_field([
                        'key'         => 'from_name',
                        'label'       => 'From Name',
                        'default'     => getFromArray('from_name', $aSettings, APPNAME),
                        'placeholder' => 'The name of the sender which recipients should see.',
                    ]);

                    // --------------------------------------------------------------------------

                    $aUrl     = parse_url(site_url());
                    $sDefault = 'nobody@' . getFromArray('host', $aUrl);

                    echo form_field([
                        'key'         => 'from_email',
                        'label'       => 'From Email',
                        'default'     => getFromArray('from_email', $aSettings, $sDeault),
                        'placeholder' => 'The email address of the sender which recipients should see.',
                        'info'        => '<strong>Note:</strong> If sending using SMTP to send email ensure this email is a valid account on the mail server. If it\'s not valid, some services will junk the email.',
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
