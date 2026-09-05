<?php

/**
 * Slot: greeting
 *
 * The first thing in the content well, above the body view.
 *
 * Both branches are present on purpose: a recipient with no first name still
 * gets greeted.
 *
 * @var \stdClass|null $emailObject
 */

?>
{{#sentTo.first_name}}
<p>Hi {{sentTo.first_name}},</p>
{{/sentTo.first_name}}
{{^sentTo.first_name}}
<p>Hi,</p>
{{/sentTo.first_name}}
