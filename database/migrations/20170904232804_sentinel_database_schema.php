<?php
use Phinx\Migration\AbstractMigration;

class SentinelDatabaseSchema extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
/*
# Dump of table activations
# ------------------------------------------------------------

CREATE TABLE `activations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/
      if (!$this->hasTable('activations') ) {
        $table = $this->table('activations');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('code', 'string', array('limit' => 255))
          ->addColumn('completed', 'boolean')
          ->addColumn('completed_at', 'datetime', array('null' => true))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      }
/*
# Dump of table persistences
# ------------------------------------------------------------

CREATE TABLE `persistences` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `persistences_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/
      if (!$this->hasTable('persistences') ) {
        $table = $this->table('persistences');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('completed', 'boolean')
          ->addColumn('code', 'string', array('limit' => 255))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      }
/*
# Dump of table reminders
# ------------------------------------------------------------

CREATE TABLE `reminders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `code` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/
      if (!$this->hasTable('reminders') ) {
        $table = $this->table('reminders');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('code', 'string', array('limit' => 255))
          ->addColumn('completed', 'boolean')
          ->addColumn('completed_at', 'datetime', array('null' => true))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      }
/*
# Dump of table roles
# ------------------------------------------------------------

CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `slug` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `permissions` text COLLATE utf8_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_slug_unique` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/
      if (!$this->hasTable('roles') ) {
        $table = $this->table('roles');
        $table
          ->addColumn('slug', 'string', array('limit' => 255))
          ->addColumn('name', 'string', array('limit' => 255))
          ->addColumn('permissions', 'text')
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->create();
      }
/*
# Dump of table role_users
# ------------------------------------------------------------

CREATE TABLE `role_users` (
  `user_id` int(10) unsigned NOT NULL,
  `role_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`user_id`,`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/
      if (!$this->hasTable('role_users') ) {
        $table = $this->table('role_users');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('role_id', 'integer', array('limit' => 10))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00'))  
          ->create();
      }
/*
# Dump of table throttle
# ------------------------------------------------------------

CREATE TABLE `throttle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `ip` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `throttle_user_id_index` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
*/
      if (!$this->hasTable('throttle') ) {
        $table = $this->table('throttle');
        $table
          ->addColumn('user_id', 'integer', array('limit' => 10))
          ->addColumn('type', 'string', array('limit' => 255))
          ->addColumn('ip', 'string', array('limit' => 255))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00')) 
          ->create();
      }
/*
# Dump of table users
# ------------------------------------------------------------

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `permissions` text COLLATE utf8_unicode_ci,
  `last_login` timestamp NULL DEFAULT NULL,
  `first_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE  `users` ADD  `username` VARCHAR( 255 ) NOT NULL AFTER  `email` ,
ADD  `displayname` VARCHAR( 255 ) NOT NULL AFTER  `username` ;
*/
      if (!$this->hasTable('users') ) {
        $table = $this->table('users');
        $table
          ->addColumn('email', 'string', array('limit' => 255))
          ->addColumn('username', 'string', array('limit' => 255))
          ->addColumn('displayname', 'string', array('limit' => 255))
          ->addColumn('password', 'string', array('limit' => 255))
          ->addColumn('permissions', 'text')
          ->addColumn('last_login', 'datetime', array('null' => true))
          ->addColumn('first_name', 'string', array('limit' => 255))
          ->addColumn('last_name', 'string', array('limit' => 255))
          ->addColumn('created_at', 'datetime', array('default' => '0000-00-00 00:00:00'))
          ->addColumn('updated_at', 'datetime', array('default' => '0000-00-00 00:00:00')) 
          ->create();
      }
    }
}

