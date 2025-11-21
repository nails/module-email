<?php

/**
 * Migration: 15
 * Started:   21/11/2025
 */

namespace Nails\Email\Database\Migration;

/**
 * Class Migration15
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration15 extends Migration14
{
    /**
     * Execute the migration
     *
     * @return void
     */
    public function execute(): void
    {
        /**
         * Apps upgrading from feature/pre-new-admin will be on 14 so the permission migration won't happen. Execute it
         * again here (safe to do) so that it definitely runs
         */
        parent::execute();

        //  And continue onto desired changes if they haven't been run already (sniff for new columns on email_archive)
        $oResult    = $this->query('SHOW COLUMNS FROM `{{NAILS_DB_PREFIX}}email_archive` LIKE "created";');
        $hasBeenRun = $oResult->rowCount() > 0;

        if (!$hasBeenRun) {
            //  main archive
            //  Add timestamp columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `created` DATETIME null AFTER `fail_reason`;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `modified` DATETIME null AFTER `created`;');
            //  Add user columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `created_by` INT UNSIGNED null default null AFTER `created`;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `modified_by` INT UNSIGNED null default null AFTER `modified`;');
            //  Add relations
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD FOREIGN KEY(`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD FOREIGN KEY(`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            //  Set timestamps, with fallback
            //  Where a queued date is known
            $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `created` = `queued` WHERE `created` IS null;');
            //  Where a queued date is not known, attempt to match with link.created
            $this->query(
                <<<EOT
                UPDATE `{{NAILS_DB_PREFIX}}email_archive` AS e
                JOIN (
                    SELECT
                        email_id,
                        MIN(created) AS min_created
                    FROM `{{NAILS_DB_PREFIX}}email_archive_link`
                    GROUP BY email_id
                ) AS l ON l.email_id = e.id
                SET e.created = l.min_created
                WHERE e.created IS NULL;
                EOT
            );
            //  Cannot infer, so use NOW() so something is set
            $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `created` = NOW() WHERE `created` IS null;');
            $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `modified` = `created` WHERE `modified` IS null;');
            //  Make not-nullable
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `created` `created` DATETIME NOT null;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` CHANGE `modified` `modified` DATETIME NOT null;');

            //  Archive links
            //  Modify/add timestamp columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` MODIFY COLUMN `created` DATETIME NOT NULL AFTER `is_html`;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` ADD `modified` DATETIME null AFTER `created`;');
            //  Add user columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` ADD `created_by` INT UNSIGNED null default null AFTER `created`;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` ADD `modified_by` INT UNSIGNED null default null AFTER `modified`;');
            //  Add relations
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` ADD FOREIGN KEY(`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` ADD FOREIGN KEY(`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            //  Set timestamps
            $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive_link` SET `modified` = `created` WHERE `modified` IS null;');
            //  Make not-nullable
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_link` CHANGE `modified` `modified` DATETIME NOT null;');

            //  Track links
            //  Modify timestamp columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` ADD `modified` DATETIME null AFTER `created`;');
            //  Add user columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` ADD `created_by` INT UNSIGNED null default null AFTER `created`;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` ADD `modified_by` INT UNSIGNED null default null AFTER `modified`;');
            //  Add relations
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` ADD FOREIGN KEY(`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` ADD FOREIGN KEY(`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            //  Set timestamps
            $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive_track_link` SET `modified` = `created` WHERE `modified` IS null;');
            //  Make not-nullable
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_link` CHANGE `modified` `modified` DATETIME NOT null;');

            //  Track opens
            //  Modify timestamp columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` ADD `modified` DATETIME null AFTER `created`;');
            //  Add user columns
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` ADD `created_by` INT UNSIGNED null default null AFTER `created`;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` ADD `modified_by` INT UNSIGNED null default null AFTER `modified`;');
            //  Add relations
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` ADD FOREIGN KEY(`created_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` ADD FOREIGN KEY(`modified_by`) REFERENCES `{{NAILS_DB_PREFIX}}user` (`id`) ON DELETE SET null;');
            //  Set timestamps
            $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive_track_open` SET `modified` = `created` WHERE `modified` IS null;');
            //  Make not-nullable
            $this->query('ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive_track_open` CHANGE `modified` `modified` DATETIME NOT null;');
        }
    }
}
