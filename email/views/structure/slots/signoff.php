<?php

/**
 * Slot: sign-off
 *
 * Rendered at the bottom of the content well, below the body view. Empty
 * unless the `sign_off` setting has been set in admin, in which case nothing
 * is emitted at all and no empty paragraph is left behind.
 *
 * The value is emitted as-is rather than escaped. That is deliberate: it is
 * written by an administrator, it is the same trust boundary as the email
 * template override admin, and escaping it would break both intentional markup
 * and the `{{ config('FOO') }}` syntax. Mustache runs after the header, body
 * and footer have been concatenated, so the value shares the email's context —
 * `The {{appName}} Team` and `{{sentTo.first_name}}` both work.
 *
 * @var \stdClass|null $emailObject
 */

$sSignOff = trim((string) appSetting(
    \Nails\Email\Settings\General::KEY_SIGN_OFF,
    \Nails\Email\Constants::MODULE_SLUG
));

if ($sSignOff !== '') {
    ?>
    <p class="signoff last"><?=nl2br($sSignOff)?></p>
    <?php
}
