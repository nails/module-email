<?php

namespace Nails\Email\FindReplace\Exclusion;

use Nails\FindReplace\Interfaces\Exclusion;

class Package implements Exclusion
{
    public function hidden(): array
    {
        return [
            NAILS_DB_PREFIX . 'email_archive',
            NAILS_DB_PREFIX . 'email_archive_link',
            NAILS_DB_PREFIX . 'email_template_override.subject_original_hash',
            NAILS_DB_PREFIX . 'email_template_override.body_html_original_hash',
            NAILS_DB_PREFIX . 'email_template_override.body_text_original_hash',
        ];
    }

    public function readOnly(): array
    {
        return [];
    }
}
