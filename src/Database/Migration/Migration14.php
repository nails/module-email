<?php

/**
 * Migration: 14
 * Started:   21/11/2025
 */

namespace Nails\Email\Database\Migration;

use Nails\Common\Interfaces;
use Nails\Common\Traits;

/**
 * Class Migration14
 *
 * @package Nails\Cms\Database\Migration
 */
class Migration14 implements Interfaces\Database\Migration
{
    use Traits\Database\Migration;

    // --------------------------------------------------------------------------

    /**
     * Execute the migration
     *
     * @return void
     */
    public function execute(): void
    {
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
        $this->query('UPDATE `{{NAILS_DB_PREFIX}}email_archive` SET `created` = `queued` WHERE `created` IS null;');
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
