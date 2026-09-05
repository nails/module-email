<?php

/**
 * Slot: footer address, plain text part
 *
 * @var \stdClass|null $emailObject
 */

echo trim((string) appSetting(
    \Nails\Email\Settings\General::KEY_FOOTER_ADDRESS,
    \Nails\Email\Constants::MODULE_SLUG
));
