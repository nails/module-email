<?php

use Nails\Config;
use Nails\Email;
use Nails\Environment;

/** @var Email\Service\Emailer $oEmailer */
$oEmailer = \Nails\Factory::service('Emailer', Email\Constants::MODULE_SLUG);

?>
<div class="group-utilities send-test">
    <?=form_open();?>
    <fieldset>
        <legend>Recipient</legend>
        <?php

        echo form_field([
            'key'         => 'recipient',
            'label'       => 'Email',
            'required'    => true,
            'placeholder' => 'Type recipient\'s email address',
            'default'     => activeUser('email'),
        ]);
        echo form_field_dropdown([
            'key'      => 'type',
            'label'    => 'Type',
            'required' => true,
            'class'    => 'select2',
            'options'  => $aTypes,
            'default'  => 'test_email',
        ]);

        ?>
    </fieldset>
    <?php

    echo Nails\Admin\Helper::floatingControls([
        'save' => [
            'text' => 'Send Test Email'
        ]
    ]);

    echo form_close();

    if (isSuperUser()) {

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
                'default'  => mask(\Nails\Config::get('EMAIL_PASSWORD') ?? ''),
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
            <p class="alert alert-warning">
                These values are applied on <strong>non-production</strong> environments to prevent the accidental
                release of mail.
            </p>
            <?php

            echo form_field([
                'key'      => '',
                'label'    => 'To Override',
                'default'  => Environment::not(Environment::ENV_PROD)
                    ? Config::get('EMAIL_OVERRIDE')
                    : '',
                'info'     => 'If defined, all email is routed to this address',
                'readonly' => true,
            ]);

            echo form_field_textarea([
                'key'      => '',
                'label'    => 'Whitelist',
                'default'  => Environment::not(Environment::ENV_PROD)
                    ? implode(PHP_EOL, $oEmailer::getWhitelist())
                    : '',
                'info'     => 'If defined, email is only released if the "to" address is whitelisted. This list supports regular expressions.',
                'readonly' => true,
            ]);
            ?>
        </fieldset>
        <?php
    }

    ?>
</div>
