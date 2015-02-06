ALTER TABLE `{{NAILS_DB_PREFIX}}email_archive` ADD `queued` DATETIME  NULL  AFTER `user_email`;
