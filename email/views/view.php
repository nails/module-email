<div class="email-debugger">
    <div class="header">
        This page is viewable in development environments only.
        <a href="http://docs.nailsapp.co.uk">
            <img src="<?=NAILS_ASSETS_URL?>img/nails-logo.png" id="nailsLogo"/>
        </a>
    </div>
    <div class="sub-header">
        <div class="column variables">Variables</div>
        <div class="column html">HTML</div>
        <div class="column text">TEXT</div>
    </div>
    <div class="body">
        <div class="column variables">
            <pre><?=print_r($oEmail->data, true);?></pre>
        </div>
        <div class="column html">
            <iframe srcdoc="<?=htmlentities($oEmail->body->html)?>"></iframe>
        </div>
        <div class="column text">
            <pre><?=$oEmail->body->text?></pre>
        </div>
    </div>
</div>
