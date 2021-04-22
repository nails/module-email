<?php

/**
 * Migration:   0
 * Started:     09/01/2015
 * Finalised:   09/01/2015
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Console\Migrate\Base;

class Migration0 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}email_archive` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `ref` varchar(10) DEFAULT NULL,
              `user_id` int(11) unsigned DEFAULT NULL,
              `user_email` varchar(150) DEFAULT NULL,
              `sent` datetime DEFAULT NULL,
              `status` enum('SENT','BOUNCED','OPENED','REJECTED','DELAYED','SOFT_BOUNCED','MARKED_AS_SPAM','CLICKED','FAILED') NOT NULL DEFAULT 'SENT',
              `type` varchar(50) NOT NULL DEFAULT '',
              `email_vars` longtext,
              `internal_ref` int(11) unsigned DEFAULT NULL,
              `read_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
              `link_click_count` tinyint(3) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`),
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}email_archive_link` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `email_id` int(11) unsigned NOT NULL,
              `url` varchar(500) NOT NULL DEFAULT '',
              `title` varchar(255) DEFAULT NULL,
              `created` datetime NOT NULL,
              `is_html` tinyint(1) unsigned NOT NULL,
              PRIMARY KEY (`id`),
              KEY `email_id` (`email_id`),
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_link_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `{{NAILS_DB_PREFIX}}email_archive` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `email_id` int(11) unsigned NOT NULL,
              `link_id` int(11) unsigned NOT NULL,
              `user_id` int(11) unsigned DEFAULT NULL,
              `created` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `email_id` (`email_id`),
              KEY `link_id` (`link_id`),
              KEY `user_id` (`user_id`),
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_track_link_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `{{NAILS_DB_PREFIX}}email_archive` (`id`) ON DELETE CASCADE,
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_track_link_ibfk_2` FOREIGN KEY (`link_id`) REFERENCES `{{NAILS_DB_PREFIX}}email_archive_link` (`id`) ON DELETE CASCADE,
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_track_link_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `email_id` int(11) unsigned NOT NULL,
              `user_id` int(11) unsigned DEFAULT NULL,
              `created` datetime NOT NULL,
              PRIMARY KEY (`id`),
              KEY `email_id` (`email_id`),
              KEY `user_id` (`user_id`),
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_track_open_ibfk_1` FOREIGN KEY (`email_id`) REFERENCES `{{NAILS_DB_PREFIX}}email_archive` (`id`) ON DELETE CASCADE,
              CONSTRAINT `{{NAILS_DB_PREFIX}}email_archive_track_open_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ");
    }
}
