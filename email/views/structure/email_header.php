<?php

/**
 * The framework email header
 *
 * This view and `email_footer.php` are two halves of one document. The header
 * opens everything down to the content well and closes none of it —
 * `<html>`, `<body>`, the `.body-wrap` table and its row, the `.container`
 * cell, the Outlook ghost table, the `.content` div, the `.main` table and its
 * row, and the `.content-wrap` cell — and the footer closes all nine in
 * order. They cannot be overridden independently of one another.
 *
 * There are no `style` attributes here by design — all of the styling lives in
 * `assets/sass/email/` and arrives via the `<style>` block below. The HTML
 * presentational attributes (`width`, `align`, `valign`, `bgcolor`,
 * `cellpadding`, `cellspacing`, `border`) are kept: they are not CSS, and they
 * are a free fallback in the handful of clients which strip `<style>`.
 *
 * There are two deliberate exceptions, both because they break outright when
 * the `<style>` block is stripped: the preheader below, whose hiding rules are
 * repeated inline, and the footer's tracker pixel.
 *
 * Rather than overriding this view, drop a file into
 * `application/modules/email/views/structure/slots/` to replace one of the
 * named slots below. See the README.
 *
 * @var \stdClass|null $emailObject
 */

/** @var \Nails\Common\Service\View $oView */
$oView = \Nails\Factory::service('View');

$aSlotData = ['emailObject' => $emailObject ?? null];

$fRenderSlot = function (string $sSlot) use ($oView, $aSlotData): string {
    return trim($oView->load('email/structure/slots/' . $sSlot, $aSlotData, true));
};

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="x-apple-disable-message-reformatting"/>
    <meta name="format-detection" content="telephone=no,address=no,email=no,date=no"/>
    <!--
        Dark mode is honoured by Apple Mail, Mail on iOS and Outlook.com.
        Gmail ignores both of these and force-inverts on its own terms.
    -->
    <meta name="color-scheme" content="light dark"/>
    <meta name="supported-color-schemes" content="light dark"/>
    <title>{{email_subject}}</title>
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <![endif]-->
    <style type="text/css">
        <?php require __DIR__ . '/../../../assets/css/email.min.css'; ?>
    </style>
    <!--[if mso]>
    <style type="text/css">
        /* Word has no webfont support and no rounded corners; give it
           something it can render rather than something it will mangle */
        body, table, td, th, p, a, li, h1, h2, h3, h4, h5, h6 {
            font-family: Arial, Helvetica, sans-serif !important;
        }
        .main, .alert, .alert-header, .btn, .button, .btn-table__cell,
        .panel, .badge, .code, .heads-up, pre {
            border-radius: 0 !important;
        }
    </style>
    <![endif]-->
    <?php echo $fRenderSlot('styles'); ?>
</head>

<body itemscope itemtype="http://schema.org/EmailMessage" bgcolor="#f1f3f6">
<div class="preheader" style="display:none;visibility:hidden;opacity:0;color:transparent;height:0;width:0;max-height:0;max-width:0;overflow:hidden;font-size:1px;line-height:1px;mso-hide:all;">{{preheader}}</div>
<table class="body-wrap" role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f3f6">
    <tr>
        <td class="gutter" width="20" valign="top">&nbsp;</td>
        <td class="container" align="center" valign="top">
            <!--[if mso]>
            <table role="presentation" width="600" align="center" cellpadding="0" cellspacing="0" border="0"><tr><td>
            <![endif]-->
            <div class="content">
                <?php
                $sMasthead = $fRenderSlot('masthead');
                if ($sMasthead !== '') {
                    echo '<div class="masthead">' . $sMasthead . '</div>' . PHP_EOL;
                }
                ?>
                <table class="main" role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" align="center" bgcolor="#ffffff">
                    <tr>
                        <td class="alert alert-header" align="center" valign="top" bgcolor="#d53354">
                            {{email_subject}}
                        </td>
                    </tr>
                    <?php
                    if (\Nails\Environment::not(\Nails\Environment::ENV_PROD)) {
                        ?><tr>
                        <td class="alert alert-header alert-header--warning" align="center" valign="top" bgcolor="#f0ad00">
                            This email was sent from a testing environment.
                        </td>
                    </tr>
                    <?php
                    }
                    ?><tr>
                        <td class="content-wrap" align="left" valign="top">
                            <?php echo $fRenderSlot('greeting') . "\n"; ?>
