<?php

namespace Tests\FindReplace\Exclusion;

use Nails\Email\FindReplace\Exclusion\Package;
use PHPUnit\Framework\TestCase;

class PackageTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!defined('NAILS_DB_PREFIX')) {
            define('NAILS_DB_PREFIX', 'nails_');
        }
    }

    public function testHidden(): void
    {
        self::assertSame(
            [
                NAILS_DB_PREFIX . 'email_archive',
                NAILS_DB_PREFIX . 'email_archive_link',
                NAILS_DB_PREFIX . 'email_template_override.subject_original_hash',
                NAILS_DB_PREFIX . 'email_template_override.body_html_original_hash',
                NAILS_DB_PREFIX . 'email_template_override.body_text_original_hash',
            ],
            (new Package())->hidden()
        );
    }

    public function testReadOnly(): void
    {
        self::assertSame(
            [],
            (new Package())->readOnly()
        );
    }
}
