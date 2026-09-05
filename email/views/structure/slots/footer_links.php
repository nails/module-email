<?php

/**
 * Slot: footer links
 *
 * The view-online and unsubscribe links. The separator lives inside the
 * unsubscribe section so that it only appears when there is something on both
 * sides of it — the two links used to run together with nothing between them.
 * It pads itself with entities as well as CSS, so the links stay apart in a
 * client which has stripped the `<style>` block.
 *
 * The unsubscribe URL is emitted unescaped (`{{{ }}}`), as it always has been:
 * it carries an encrypted token, and not every client decodes entities inside
 * an `href` reliably.
 *
 * @var \stdClass|null $emailObject
 */

?>
{{#url.viewOnline}}<a href="{{url.viewOnline}}">View this email online</a>{{/url.viewOnline}}{{#url.unsubscribe}}<span class="content-block__separator">&nbsp;&middot;&nbsp;</span><a href="{{{url.unsubscribe}}}">Unsubscribe</a>{{/url.unsubscribe}}
