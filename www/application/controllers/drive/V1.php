<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/libraries/Filesystem.php';

class V1 extends MY_Controller {

    private static $whitelisted_characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._ ';
    private $filestorage;

    public function __construct() {
        parent::__construct();

        $this->load->database();
    }

    private function init_filestorage() {
        if ($this->filestorage) {
            return true;
        }

        try {
            $this->filestorage = new Filesystem($this->config->item('upload_file_path'));
            return true;
        } catch (Exception $e) {
            $this->return_error($e->getMessage(), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        }
    }

    public function file_post()
    {
        if (!$this->init_filestorage()) {
            return;
        }
        $name = trim($this->input->post('name'));
        $overwrite = $this->input->post('overwrite') == '1';

        if (!$this->validate_name($name)) {
            $this->return_error('name contains invalid characters. only these characters are accepted: ' . self::$whitelisted_characters);
            return;
        }

        if (!isset($_FILES['file'])) {
            $this->return_error('file to be uploaded is required');
            return;
        }

        $file_with_same_name = $this->get_file($name);
        if (!$overwrite && $file_with_same_name) {
            $this->return_error('file named ' . $name . ' exists');
            return;
        }

        $md5 = md5_file($_FILES['file']['tmp_name']);
        $content_type = mime_content_type($_FILES['file']['tmp_name']);
        $size = filesize($_FILES['file']['tmp_name']);

        $similar_file = $this->db->get_where('files', ['token' => $md5, 'content_type' => $content_type, 'size' => $size], 1)->result_array();
        if ($similar_file) {
            $similar_file = current($similar_file);
            $path = $similar_file['path'];
        } else {
            $path = implode('-', [generate_random_string('abcdefghijklmnopqrstuvwxyz0123456789'), $md5]);
            $path = $this->get_subfolders_by_filename($path) . '/' . $path;

            try {
                $success = $this->filestorage->write($_FILES['file']['tmp_name'], $path);
            } catch (Exception $e) {
                $this->return_error($e->getMessage(), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }

            if (!$success) {
                $this->return_error('failed to upload file');
                return;
            }
        }

        if ($file_with_same_name) {
            $success = $this->db->update('files', [
                'path' => $path,
                'token' => $md5,
                'size' => $size,
                'content_type' => $content_type
            ], [
                'id' => $file_with_same_name['id']
            ]);
        } else {
            $success = $this->db->insert('files', [
                'name' => $name,
                'path' => $path,
                'token' => $md5,
                'size' => $size,
                'content_type' => $content_type
            ]);
        }
        if (!$success) {
            $error = $this->db->error();
            if ($error) {
                log_message('error', 'database error. ' . $error['code'] . ' ' . $error['message']);
            }
            $this->return_error('failed to write to database', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $data = [
            'name' => $name
        ];
        $this->return_ok($data, $file_with_same_name && $overwrite ? REST_Controller::HTTP_ACCEPTED : REST_Controller::HTTP_CREATED);
    }

    public function file_get() {
        if (!$this->init_filestorage()) {
            return;
        }

        $name = trim($this->input->get('name'));
        $file = $this->get_file($name);
        if (!$file) {
            $this->output->set_header('Content-Type: application/octet-stream');
            $this->output->set_status_header(404, 'Not Found');
            return;
        }

        header('Content-Length: '. $file['size']);
        header('Content-Type: ' . $file['content_type']);
        header('Content-Disposition: inline; filename="' . addslashes($file['name']) . '"');
        echo $this->filestorage->read($file['path']);
    }

    public function file_delete() {
        if (!$this->init_filestorage()) {
            return;
        }

        $name = trim($this->input->get('name'));
        $file = $this->get_file($name);
        if (!$file) {
            $this->return_error('file not found', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $success = $this->filestorage->delete($file['path']);
        if (!$success) {
            $this->return_error('failed to delete file', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $success = $this->db->delete('files', ['id' => $file['id']]);
        if (!$success) {
            $error = $this->db->error();
            if ($error) {
                log_message('error', 'database error. ' . $error['code'] . ' ' . $error['message']);
            }
            $this->return_error('failed to delete from database', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $this->return_ok(null, REST_Controller::HTTP_OK);
    }

    private function get_file($name) {
        $this->db->where('`name` LIKE ' . $this->db->escape($name));
        $this->db->limit(1);
        $rows = $this->db->get('files')->result_array();
        if (!$rows) {
            return null;
        }

        return current($rows);
    }

    private function validate_name($name) {
        for ($i = 0; $i < strlen($name); $i++) {
            if (strpos(self::$whitelisted_characters, substr($name, $i, 1)) === false) {
                return false;
            }
        }

        return true;
    }

    private function get_subfolders_by_filename($filename) {
        $subfolders = [];
        for ($i = 0; $i < 3; $i++) {
            if ($i >= strlen($filename)) {
                break;
            }
            $subfolders[] = substr($filename, $i, 1);
        }
        return implode('/', $subfolders);
    }

}
