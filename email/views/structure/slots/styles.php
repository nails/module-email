<?php

/**
 * Slot: additional stylesheet
 *
 * Rendered inside `<head>`, after the module's own `<style>` block, so
 * anything emitted here cascades over the framework.
 *
 * The module ships this empty. To use it, override it at
 * `application/modules/email/views/structure/slots/styles.php` and inline your
 * own compiled CSS:
 *
 *     <style type="text/css">
 *         <?php require NAILS_APP_PATH . 'assets/css/email.min.css'; ?>
 *     </style>
 *
 * Which pairs with cherry-picking the SCSS partials you want — see the README.
 * Note there is no CSS inliner in the pipeline, so this has to be a `<style>`
 * block rather than a `<link>`.
 *
 * @var \stdClass|null $emailObject
 */
