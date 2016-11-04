<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once __DIR__ . '/FileStorage.php';

class Filesystem implements FileStorage {
    private $folder;

    public function __construct($folder) {
        if (!file_exists($folder) || !is_writable($folder)) {
            throw new Exception('missing upload folder');
        }
        $this->folder = $folder;
    }

    public function write($source, $path) {
        $full_path = $this->folder . '/' . $path;
        $dirname = dirname($full_path);
        $success = mkdir($dirname, 0777, true);
        if (!$success || !file_exists($dirname) || !is_writable($dirname)) {
            throw new Exception('failed to write to upload folder');
        }
        $success = move_uploaded_file($source, $full_path);
        if (!$success || !file_exists($full_path)) {
            throw new Exception('failed to write to upload folder');
        }
        return $success;
    }

    public function read($path) {
        return file_get_contents($this->folder . '/' . $path);
    }

    public function delete($path) {
        return unlink($this->folder . '/' . $path);
    }
}