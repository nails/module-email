<?php

/**
 * The framework email header, plain text part
 *
 * Assembled in PHP rather than written out as a template so that a slot which
 * renders nothing leaves no blank lines behind it.
 *
 * @var \stdClass|null $emailObject
 */

/** @var \Nails\Common\Service\View $oView */
$oView = \Nails\Factory::service('View');

$aSlotData = ['emailObject' => $emailObject ?? null];

$fRenderSlot = function (string $sSlot) use ($oView, $aSlotData): string {
    return trim($oView->load('email/structure/slots/' . $sSlot, $aSlotData, true));
};

$aBlocks = array_filter([
    $fRenderSlot('masthead_plaintext'),
    '{{email_subject}}',
    '---------------',
    $fRenderSlot('greeting_plaintext'),
]);

echo implode("\n\n", $aBlocks) . "\n\n";
