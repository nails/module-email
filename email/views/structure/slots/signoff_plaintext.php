<?php

/**
 * Slot: sign-off, plain text part
 *
 * @var \stdClass|null $emailObject
 */

echo trim((string) appSetting(
    \Nails\Email\Settings\General::KEY_SIGN_OFF,
    \Nails\Email\Constants::MODULE_SLUG
));
