<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Init extends CI_Migration {
    public function up()
    {
        $queries = [
            'CREATE TABLE `files` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `size` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `content_type` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;'
        ];
        $this->db->trans_start();
        foreach ($queries as $query) {
            $this->db->query($query);
        }
        $this->db->trans_complete();
    }

    public function down()
    {
        $tables = [
            'files',
        ];
        foreach ($tables as $table) {
            $this->dbforge->drop_table($table);
        }
    }
}