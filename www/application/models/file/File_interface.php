<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/models/MyFile.php';

interface File_interface {
    public function get_similar(MyFile $file);
    public function insert(MyFile $file);
    public function update(MyFile $file);
    public function delete(MyFile $file);
    public function get_by_name($name);
    public function get_by_path($path);
    public function is_healthy();
}