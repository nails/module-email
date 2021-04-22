<?php

/**
 * Migration:   8
 * Started:     11/10/2019
 *
 * @package     Nails
 * @subpackage  module-email
 * @category    Database Migration
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration8 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}email_template_override` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `slug` varchar(150) DEFAULT NULL,
                `subject` varchar(255) DEFAULT NULL,
                `subject_original_hash` char(32) DEFAULT NULL,
                `body_html` text,
                `body_html_original_hash` char(32) DEFAULT NULL,
                `body_text` text,
                `body_text_original_hash` char(32) DEFAULT NULL,
                `created` datetime NOT NULL,
                `created_by` int(11) unsigned DEFAULT NULL,
                `modified` datetime NOT NULL,
                `modified_by` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `type` (`slug`),
                KEY `created_by` (`created_by`),
                KEY `modified_by` (`modified_by`),
                CONSTRAINT `{{NAILS_DB_PREFIX}}email_template_override_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL,
                CONSTRAINT `{{NAILS_DB_PREFIX}}email_template_override_ibfk_2` FOREIGN KEY (`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
    }
}
