<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/models/file/File_interface.php';

class File_mysql extends CI_Model implements File_interface {
    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_similar(MyFile $file) {
        if (!$file) {
            return null;
        }
        $rows = $this->db->get_where('files', [
            'token' => $file->get_token(),
            'content_type' => $file->get_content_type(),
            'size' => $file->get_size()
        ], 1)->result_array();
        if (!$rows) {
            return null;
        }

        $row = current($rows);
        return new MyFile($row['name'], $row['path'], $row['size'], $row['token'], $row['content_type']);
    }

    public function insert(MyFile $file) {
        if (!$file) {
            return false;
        }
        return $this->db->insert('files', [
            'name' => $file->get_name(),
            'path' => $file->get_path(),
            'token' => $file->get_token(),
            'size' => $file->get_size(),
            'content_type' => $file->get_content_type()
        ]);
    }

    public function update(MyFile $file) {
        if (!$file) {
            return false;
        }
        $this->db->where('`name` LIKE ' . $this->db->escape($file->get_name()));
        return $this->db->update('files', [
            'path' => $file->get_path(),
            'token' => $file->get_token(),
            'size' => $file->get_size(),
            'content_type' => $file->get_content_type()
        ]);
    }

    public function delete(MyFile $file) {
        if (!$file) {
            return false;
        }
        $this->db->where('`name` LIKE ' . $this->db->escape($file->get_name()));
        return $this->db->delete('files');
    }

    public function get_by_name($name) {
        $this->db->where('`name` LIKE ' . $this->db->escape($name));
        $this->db->limit(1);
        $rows = $this->db->get('files')->result_array();
        if (!$rows) {
            return null;
        }
        $row = current($rows);
        return new MyFile($row['name'], $row['path'], $row['size'], $row['token'], $row['content_type']);
    }

    public function get_by_path($path) {
        $this->db->where('`path` LIKE ' . $this->db->escape($path));
        $rows = $this->db->get('files')->result_array();
        if (!$rows) {
            return null;
        }
        $myfiles = [];
        foreach ($rows as $row) {
            $myfiles[] = new MyFile($row['name'], $row['path'], $row['size'], $row['token'], $row['content_type']);
        }
        return $myfiles;
    }

    public function is_healthy() {
        return $this->db->initialize();
    }
}