<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class MyFile {
    private $name;
    private $path;
    private $size;
    private $token;
    private $content_type;

    public function __construct($name, $path, $size, $token, $content_type) {
        $this->set_name($name);
        $this->set_path($path);
        $this->set_size($size);
        $this->set_token($token);
        $this->set_content_type($content_type);
    }

    public function set_name($name) {
        $this->name = $name;
        return $this;
    }

    public function set_path($path) {
        $this->path = $path;
        return $this;
    }

    public function set_size($size) {
        $this->size = $size;
        return $this;
    }

    public function set_token($token) {
        $this->token = $token;
        return $this;
    }

    public function set_content_type($content_type) {
        $this->content_type = $content_type;
        return $this;
    }

    public function get_name() {
        return $this->name;
    }

    public function get_path() {
        return $this->path;
    }

    public function get_size() {
        return $this->size;
    }

    public function get_token() {
        return $this->token;
    }

    public function get_content_type() {
        return $this->content_type;
    }
}