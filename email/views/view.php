<div class="email-debugger">
    <div class="header">
        This page is viewable in development environments only.
        <a href="http://docs.nailsapp.co.uk">
            <img src="<?=\Nails\Common\Helper\Logo::nails()?>" id="nailsLogo" />
        </a>
    </div>
    <div class="sub-header">
        <div class="column variables">Variables</div>
        <div class="column html">HTML</div>
        <div class="column text">TEXT</div>
    </div>
    <div class="body">
        <div class="column variables">
            <pre><?=htmlentities(json_encode($oEmail->data, JSON_PRETTY_PRINT), ENT_QUOTES);?></pre>
        </div>
        <div class="column html">
            <iframe srcdoc="<?=htmlentities($oEmail->body->html, ENT_QUOTES)?>"></iframe>
        </div>
        <div class="column text">
            <pre style="white-space: pre-wrap;"><?=$oEmail->body->text?></pre>
        </div>
    </div>
</div>
