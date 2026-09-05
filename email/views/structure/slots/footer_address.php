<?php

/**
 * Slot: footer address
 *
 * The postal address in the footer. Empty unless the `footer_address` setting
 * has been set in admin; see `slots/signoff.php` for why the value is not
 * escaped.
 *
 * @var \stdClass|null $emailObject
 */

$sAddress = trim((string) appSetting(
    \Nails\Email\Settings\General::KEY_FOOTER_ADDRESS,
    \Nails\Email\Constants::MODULE_SLUG
));

echo nl2br($sAddress);
