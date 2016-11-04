<?php

defined('BASEPATH') OR exit('No direct script access allowed');

interface FileStorage {
    public function write($filename, $path);
    public function read($path);
    public function delete($path);
}