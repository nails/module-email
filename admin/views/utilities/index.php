<div class="group-utilities send-test">
    <p>
        Use this form to send a test email, useful for testing that emails being sent are received by the end user.
    </p>
    <hr />
    <?=form_open();?>
        <fieldset>
            <legend>Recipient</legend>
            <?php

            //  Recipient
            $aField                = array();
            $aField['key']         = 'recipient';
            $aField['label']       = 'Email';
            $aField['default']     = set_value($aField['key']);
            $aField['required']    = true;
            $aField['placeholder'] = 'Type recipient\'s email address';

            echo form_field($aField);

            ?>
        </fieldset>
        <p>
            <?=form_submit('submit', 'Send Test Email', 'class="btn btn-primary"')?>
        </p>
    <?=form_close()?>
</div>