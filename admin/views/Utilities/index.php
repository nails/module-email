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
</div>
