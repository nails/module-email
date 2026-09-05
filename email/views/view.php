<?php

/**
 * The email debugger
 *
 * Rendered in place of the email itself on development environments; see
 * Email\Controller\View::index().
 *
 * @var \stdClass $oEmail
 */

/**
 * Forces one colour scheme onto a copy of the email
 *
 * Dark mode reaches the email as a `prefers-color-scheme` block in the
 * compiled stylesheet, which follows the browser - and so the operating
 * system - rather than anything this page can set. Rewriting the query is what
 * lets both schemes be previewed without the developer changing their desktop
 * appearance: `all` always matches, and `not all` never does.
 *
 * A pattern rather than a string replace because the minifier drops the space
 * after `@media` and an unminified build keeps it, and because an application
 * which inlines its own CSS through the `styles` slot contributes a second
 * block which has to be caught too.
 */
$fForceScheme = function (string $sHtml, bool $bDark): string {

    $sHtml = preg_replace(
        '/@media\s*\(\s*prefers-color-scheme\s*:\s*dark\s*\)\s*\{/i',
        $bDark ? '@media all{' : '@media not all{',
        $sHtml
    );

    /**
     * The metas with it, so that whatever the client draws for itself - the
     * canvas behind the card, scrollbars, anything the email does not paint -
     * follows the pane rather than the desktop.
     */
    return preg_replace(
        '/(<meta name="(?:color-scheme|supported-color-schemes)" content=")[^"]*(")/i',
        '${1}' . ($bDark ? 'dark' : 'light') . '${2}',
        $sHtml
    );
};

?>
<div class="email-debugger">
    <?php
    /**
     * Ahead of both the sub-header which labels them and the body which holds
     * the panes: a sibling selector cannot look upwards, and the toggle drives
     * markup in each.
     */
    ?>
    <input type="radio" name="scheme" id="scheme-system" class="scheme-input" checked/>
    <input type="radio" name="scheme" id="scheme-light" class="scheme-input"/>
    <input type="radio" name="scheme" id="scheme-dark" class="scheme-input"/>
    <div class="header">
        This page is viewable in development environments only.
        <span class="note">
            The Dark pane shows Apple Mail, Mail on iOS and Outlook.com; Gmail ignores the
            colour scheme and force-inverts on its own terms.
        </span>
        <a href="http://docs.nailsapp.co.uk">
            <img src="<?=\Nails\Common\Helper\Logo::nails()?>" id="nailsLogo"/>
        </a>
    </div>
    <div class="sub-header">
        <div class="column variables">Variables</div>
        <div class="column html">
            HTML
            <span class="scheme-toggle">
                <label for="scheme-system">System</label>
                <label for="scheme-light">Light</label>
                <label for="scheme-dark">Dark</label>
            </span>
        </div>
        <div class="column text">TEXT</div>
    </div>
    <div class="body">
        <div class="column variables">
            <pre><?=htmlentities(json_encode($oEmail->data, JSON_PRETTY_PRINT), ENT_QUOTES);?></pre>
        </div>
        <div class="column html">
            <?php
            //  Unmodified: the only pane which exercises the real media query
            ?>
            <iframe class="pane pane--system" srcdoc="<?=htmlentities($oEmail->body->html, ENT_QUOTES)?>"></iframe>
            <iframe class="pane pane--light" srcdoc="<?=htmlentities($fForceScheme($oEmail->body->html, false), ENT_QUOTES)?>"></iframe>
            <iframe class="pane pane--dark" srcdoc="<?=htmlentities($fForceScheme($oEmail->body->html, true), ENT_QUOTES)?>"></iframe>
        </div>
        <div class="column text">
            <pre style="white-space: pre-wrap;"><?=$oEmail->body->text?></pre>
        </div>
    </div>
</div>
