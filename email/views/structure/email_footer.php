<?php

/**
 * The framework email footer
 *
 * The other half of `email_header.php`; closes everything it left open, in
 * order. See that file for the conventions this one follows.
 *
 * The tracker pixel keeps its inline dimensions deliberately: it has to
 * collapse even in a client which has stripped the `<style>` block, or it
 * shows up as a broken image.
 *
 * @var \stdClass|null $emailObject
 */

/** @var \Nails\Common\Service\View $oView */
$oView = \Nails\Factory::service('View');

$aSlotData = ['emailObject' => $emailObject ?? null];

$fRenderSlot = function (string $sSlot) use ($oView, $aSlotData): string {
    return trim($oView->load('email/structure/slots/' . $sSlot, $aSlotData, true));
};

$sSignOff = $fRenderSlot('signoff');
$sLinks   = $fRenderSlot('footer_links');
$sAddress = $fRenderSlot('footer_address');

//  Rendered inside the content well, immediately after the body view
if ($sSignOff !== '') {
    echo $sSignOff . "\n";
}

?>
                        </td>
                    </tr>
                </table>
                <div class="footer">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <?php
                        if ($sLinks !== '') {
                            ?><tr>
                            <td class="aligncenter content-block" align="center" valign="top">
                                <?php echo $sLinks . PHP_EOL; ?>
                            </td>
                        </tr>
                        <?php
                        }
                        if ($sAddress !== '') {
                            ?><tr>
                            <td class="aligncenter content-block content-block--address" align="center" valign="top">
                                <?php echo $sAddress . PHP_EOL; ?>
                            </td>
                        </tr>
                        <?php
                        }
                        ?><tr>
                            <td class="aligncenter content-block content-block--reference" align="center" valign="top">
                                Email Ref: {{emailRef}}
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            <!--[if mso]>
            </td></tr></table>
            <![endif]-->
        </td>
        <td class="gutter" width="20" valign="top">&nbsp;</td>
    </tr>
</table>
{{#url.trackerImg}}
<img src="{{url.trackerImg}}" width="1" height="1" border="0" alt="" style="display:block;width:1px;height:1px;max-height:1px;max-width:1px;border:0;overflow:hidden;"/>
{{/url.trackerImg}}
</body>
</html>
