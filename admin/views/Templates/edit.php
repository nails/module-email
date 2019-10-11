<div class="alert alert-warning">
    <p>
        <strong>Editing email templates can have unintended side-effects</strong>
    </p>
    <p>
        Be careful whilst editing email templates, invalid markup can cause layout to break or for the email to fail
        sending.
    </p>
</div>
<div class="alert alert-info">
    <p>
        <strong>Variables and simple logic</strong>
    </p>
    <p>
        Templates support the <a href="https://mustache.github.io/mustache.5.html" target="_blank">Mustache</a>
        templating language.
    </p>
</div>
<?=form_open()?>
<fieldset>
    <legend>Overrides</legend>
    <?php
    echo form_field([
        'key'         => 'subject',
        'label'       => 'Subject',
        'info'        => 'Overrides the subject, including any runtime overrides which may exist (i.e set at time of sending)',
        'placeholder' => $sDefaultSubject,
        'default'     => $oOverride->subject ?? $sDefaultSubject,
        'info'        => $bDefaultSubjectChanged ? '<span class="alert alert-warning">The template has changed since this override was created.</span>' : '',
    ]);
    echo form_field_textarea([
        'key'         => 'body_html',
        'label'       => 'Body (HTML)',
        'placeholder' => $sDefaultBodyHtml,
        'default'     => $oOverride->body_html ?? $sDefaultBodyHtml,
        'info'        => $bDefaultBodyHtmlChanged ? '<span class="alert alert-warning">The template has changed since this override was created.</span>' : '',
    ]);
    echo form_field_textarea([
        'key'         => 'body_text',
        'label'       => 'Body (TEXT)',
        'placeholder' => $sDefaultBodyText,
        'default'     => $oOverride->body_text ?? $sDefaultBodyText,
        'info'        => $bDefaultBodyTextChanged ? '<span class="alert alert-warning">The template has changed since this override was created.</span>' : '',
    ]);
    ?>
</fieldset>
<div class="admin-floating-controls">
    <button type="submit" class="btn btn-primary">
        Save Changes
    </button>
</div>
<?=form_open()?>
