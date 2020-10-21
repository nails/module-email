<div class="group-utilities send-test">
    <p>
        Use this form to send a test email, useful for testing that emails being sent are received by the end user.
    </p>
    <hr/>
    <?=form_open();?>
    <fieldset>
        <legend>Recipient</legend>
        <?php

        echo form_field([
            'key'         => 'recipient',
            'label'       => 'Email',
            'required'    => true,
            'placeholder' => 'Type recipient\'s email address',
        ]);

        ?>
    </fieldset>
    <p>
        <?=form_submit('submit', 'Send Test Email', 'class="btn btn-primary"')?>
    </p>
    <?=form_close()?>
    <?php
    if (isSuperuser()) {

        ?>
        <hr>
        <h2>
            Email configuration
        </h2>
        <fieldset>
            <legend>SMTP</legend>
            <?php
            echo form_field([
                'key'      => '',
                'label'    => 'Host',
                'default'  => \Nails\Config::get('EMAIL_HOST'),
                'readonly' => true,
            ]);

            echo form_field([
                'key'      => '',
                'label'    => 'User',
                'default'  => \Nails\Config::get('EMAIL_USERNAME'),
                'readonly' => true,
            ]);

            echo form_field([
                'key'      => '',
                'label'    => 'Password',
                'default'  => mask(\Nails\Config::get('EMAIL_PASSWORD')),
                'readonly' => true,
            ]);

            echo form_field([
                'key'      => '',
                'label'    => 'Port',
                'default'  => \Nails\Config::get('EMAIL_PORT'),
                'readonly' => true,
            ]);
            ?>
        </fieldset>
        <fieldset>
            <legend>Overrides and Whitelist</legend>
            <?php

            echo form_field([
                'key'      => '',
                'label'    => 'To Override',
                'default'  => \Nails\Config::get('EMAIL_OVERRIDE') ?: \Nails\Config::get('APP_DEVELOPER_EMAIL'),
                'info'     => 'If defined, all email is routed to this address',
                'readonly' => true,
            ]);

            echo form_field_textarea([
                'key'      => '',
                'label'    => 'Whitelist',
                'default'  => \Nails\Config::get('EMAIL_WHITELIST'),
                'info'     => 'If defined, email is only released if the "to" address is whitelisted',
                'readonly' => true,
            ]);
            ?>
        </fieldset>
        <?php
    }

    ?>
</div>
