<div class="group-settings site">
    <p>
        Configure the way the app sends email.
    </p>
    <hr />
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

            $display = $this->input->post('activeTab') == 'tab-sender' || !$this->input->post('activeTab') ? 'active' : '';

            ?>
            <div class="tab-page tab-sender <?=$display?>">
                <div class="fieldset">
                    <?php

                        $field                = array();
                        $field['key']         = 'from_name';
                        $field['label']       = 'From Name';
                        $field['default']     = appSetting($field['key'], 'nailsapp/module-email') ? appSetting($field['key'], 'nailsapp/module-email') : APP_NAME;
                        $field['placeholder'] = 'The name of the sender which recipients should see.';

                        echo form_field($field);

                        // --------------------------------------------------------------------------

                        $url     = parse_url(site_url());
                        $default = 'nobody@' . $url['host'];

                        $field                = array();
                        $field['key']         = 'from_email';
                        $field['label']       = 'From Email';
                        $field['default']     = appSetting($field['key'], 'nailsapp/module-email') ? appSetting($field['key'], 'nailsapp/module-email') : $default;
                        $field['placeholder'] = 'The email address of the sender which recipients should see.';
                        $field['info']        = '<strong>Note:</strong> If sending using SMTP to send email ensure this email is a valid account on the mail server. If it\'s not valid, some services will junk the email.';

                        echo form_field($field);

                    ?>
                </fieldset>
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