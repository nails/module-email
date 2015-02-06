<div class="group-utilities send-test">
    <p>
        Use this form to send a test email, useful for testing that emails being sent are received by the end user.
    </p>
    <hr />
    <?php

    echo form_open();

        ?>
        <fieldset>
            <legend>Recipient</legend>
            <?php

                //  Recipient
                $field                = array();
                $field['key']         = 'recipient';
                $field['label']       = 'Email';
                $field['default']     = set_value($field['key']);
                $field['required']    = TRUE;
                $field['placeholder'] = 'Type recipient\'s email address';

                echo form_field($field);

            ?>
        </fieldset>
        <?php

    echo '<p>';
        echo form_submit('submit', 'Send Test Email', 'class="awesome"');
    echo '</p>';
    echo form_close();

    ?>
</div>