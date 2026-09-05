<?php

/**
 * Slot: greeting, plain text part
 *
 * @var \stdClass|null $emailObject
 */

?>
{{#sentTo.first_name}}
Hi {{sentTo.first_name}},
{{/sentTo.first_name}}
{{^sentTo.first_name}}
Hi,
{{/sentTo.first_name}}
