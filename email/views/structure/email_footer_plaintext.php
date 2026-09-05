<?php

/**
 * The framework email footer, plain text part
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
    $fRenderSlot('signoff_plaintext'),
    '---------------',
    $fRenderSlot('footer_links_plaintext'),
    $fRenderSlot('footer_address_plaintext'),
    'Email Ref: {{emailRef}}',
]);

echo "\n\n" . implode("\n\n", $aBlocks) . "\n";
