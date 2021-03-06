<?php

/**
 * This config file defines email types for this module.
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Config
 * @author      Nails Dev Team
 * @link
 */

$config['email_types'] = [
    (object) [
        'slug'            => 'test_email',
        'name'            => 'Test Email',
        'description'     => 'Test email template, normally used in admin to test if recipients can receive email sent by the system',
        'template_header' => '',
        'template_body'   => 'email/email/test',
        'template_footer' => '',
        'default_subject' => 'Test email sent at {{sentAt}}',
        'can_unsubscribe' => true,
        'factory'         => \Nails\Email\Constants::MODULE_SLUG . '::EmailTest',
    ],
];
