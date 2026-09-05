<?php

/**
 * Slot: masthead
 *
 * Sits above the card, on the page background.
 *
 * The default needs no configuration: `Logo::discover()` resolves a logo from
 * the application's composer `extra.nails.data.logo_url`, then `APP_LOGO_URL`,
 * then a filesystem probe of `assets/img/logo.*`. It returns null if it finds
 * nothing, in which case the application's name is set as text instead.
 *
 * The `height` attribute is deliberate: it is not CSS, so it survives a client
 * which strips the `<style>` block, and specifying only a height leaves the
 * aspect ratio intact.
 *
 * @var \stdClass|null $emailObject
 */

$sLogo    = \Nails\Common\Helper\Logo::discover();
$sAppName = \Nails\Factory::service('MetaData')->getAppName();

if ($sLogo) {
    ?>
    <img
        src="<?=htmlspecialchars($sLogo, ENT_QUOTES)?>"
        alt="<?=htmlspecialchars((string) $sAppName, ENT_QUOTES)?>"
        class="masthead__logo"
        height="48"
        border="0"/>
    <?php
} elseif ($sAppName) {
    ?>
    <span class="masthead__name"><?=htmlspecialchars((string) $sAppName)?></span>
    <?php
}
