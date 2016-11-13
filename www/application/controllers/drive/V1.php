<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . '/libraries/Filesystem.php';
require_once APPPATH . '/models/MyFile.php';

class V1 extends MY_Controller {

    private static $whitelisted_characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-._ ';
    private $filestorage;

    public function __construct() {
        parent::__construct();

        $this->load->model('file/File_model');
    }

    private function init_filestorage() {
        if ($this->filestorage) {
            return true;
        }

        try {
            $this->filestorage = new Filesystem($this->config->item('upload_file_path'));
            return true;
        } catch (Exception $e) {
            $this->return_error('internal_error', $e->getMessage(), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return false;
        }
    }

    /**
     * @api {post} /drive/v1/health Health check
     * @apiName HealthCheck
     * @apiVersion 1.0.0
     * @apiGroup Drive
     * @apiDescription Check if system is healthy
     * 
     * @apiError unhealthy System is not healthy
     * 
     * @apiSuccessExample {json} Success-Response if file did not exist:
     *     HTTP/1.1 201 Created
     *     {"status":"ok"}
     * 
     * @apiExample {curl} Example usage:
     *     curl --request GET 'http://localhost:1234/drive/v1/health'
     */
    public function health_get() {
        $is_db_healthy = $this->File_model->is_healthy();
        $is_filestorage_healthy = $this->init_filestorage();
        if ($is_db_healthy && $is_filestorage_healthy) {
            $this->return_ok(null, REST_Controller::HTTP_OK);
        } else {
            $this->return_error('unhealthy', 'unhealthy', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @api {post} /drive/v1/file Upload a file
     * @apiName UploadFile
     * @apiVersion 1.0.0
     * @apiGroup File
     * @apiDescription Upload a file with specified name
     * 
     * @apiParam {String} name File name
     * @apiParam {Number} overwrite Flag to overwrite if file with specified name exists. Pass 1 to overwrite
     * @apiParam {File} file Submit file bytes in Content-Type: multipart/form-data fashion
     * 
     * @apiError invalid_name Parameter <code>name</code> has characters other than the whitelisted characters: abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789
     * @apiError missing_file Cannot find <code>file</code> parameter which should consists of the file to be uploaded in Content-Type: multipart/form-data fashion
     * @apiError file_exists File with same name exists in the database but <code>overwrite</code> is not set as 1
     * @apiError failed_upload Error happens which somehow prevents the system to save the file
     * 
     * @apiSuccessExample {json} Success-Response if file did not exist:
     *     HTTP/1.1 201 Created
     *     {"status":"ok","data":{"name":"sample.jpg"}}
     * @apiSuccessExample {json} Success-Response if file existed and got overwritten:
     *     HTTP/1.1 202 Accepted
     *     {"status":"ok","data":{"name":"sample.jpg"}}
     * 
     * @apiExample {curl} Example usage:
     *     curl --request POST --form "file=@sample.jpg" --form 'name=sample.jpg' 'http://localhost:1234/drive/v1/file'
     */
    public function file_post()
    {
        if (!$this->init_filestorage()) {
            return;
        }
        $name = trim($this->input->post('name'));
        $overwrite = $this->input->post('overwrite') == '1';

        if (!$this->validate_name($name)) {
            $this->return_error('invalid_name', 'name contains invalid characters. only these characters are accepted: ' . self::$whitelisted_characters);
            return;
        }

        if (!isset($_FILES['file'])) {
            $this->return_error('missing_file', 'file to be uploaded is required');
            return;
        }

        $file_with_same_name = $this->File_model->get_by_name($name);
        if (!$overwrite && $file_with_same_name) {
            $this->return_error('file_exists', 'file named ' . $name . ' exists');
            return;
        }

        $md5 = md5_file($_FILES['file']['tmp_name']);
        $content_type = mime_content_type($_FILES['file']['tmp_name']);
        $size = filesize($_FILES['file']['tmp_name']);

        $similar_file = $this->File_model->get_similar(new MyFile(null, null, $size, $md5, $content_type));
        if ($similar_file) {
            $path = $similar_file->get_path();
        } else {
            $path = implode('-', [generate_random_string('abcdefghijklmnopqrstuvwxyz0123456789'), $md5]);

            try {
                $success = $this->filestorage->write($_FILES['file']['tmp_name'], $path);
            } catch (Exception $e) {
                $this->return_error('failed_upload', $e->getMessage(), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }

            if (!$success) {
                $this->return_error('failed_upload', 'failed to upload file');
                return;
            }
        }

        if ($file_with_same_name) {
            $success = $this->File_model->update(new MyFile($file_with_same_name->get_name(), $path, $size, $md5, $content_type));
        } else {
            $success = $this->File_model->insert(new MyFile($name, $path, $size, $md5, $content_type));
        }
        if (!$success) {
            $this->return_error('failed_upload', 'failed to write to database', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $data = [
            'name' => $name
        ];
        $this->return_ok($data, $file_with_same_name && $overwrite ? REST_Controller::HTTP_ACCEPTED : REST_Controller::HTTP_CREATED);
    }

    /**
     * @api {get} /drive/v1/file Download a file
     * @apiName DownloadFile
     * @apiVersion 1.0.0
     * @apiGroup File
     * @apiDescription Download a file by name
     * 
     * @apiParam {String} name File name
     * 
     * @apiErrorExample {} Error-Response:
     *     HTTP/1.1 404 Not Found
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     Content-Type: image/jpeg
     *     Content-Disposition: inline; filename="sample.jpg"
     *     <bytes>
     * 
     * @apiExample {curl} Example usage:
     *     curl -s --request GET 'http://localhost:1234/drive/v1/file?name=sample.jpg' > get-sample.jpg
     */
    public function file_get() {
        if (!$this->init_filestorage()) {
            return;
        }

        $name = trim($this->input->get('name'));
        $file = $this->File_model->get_by_name($name);
        if (!$file) {
            $this->output->set_header('Content-Type: application/octet-stream');
            $this->output->set_status_header(404, 'Not Found');
            return;
        }

        header('Content-Length: '. $file->get_size());
        header('Content-Type: ' . $file->get_content_type());
        header('Content-Disposition: inline; filename="' . addslashes($file->get_name()) . '"');
        echo $this->filestorage->read($file->get_path());
    }

    /**
     * @api {delete} /drive/v1/file Delete a file
     * @apiName DeleteFile
     * @apiVersion 1.0.0
     * @apiGroup File
     * @apiDescription Delete a file with specified name
     * 
     * @apiParam {String} name File name
     * 
     * @apiError not_found File with name <code>name</code> does not exist
     * @apiError failed Failed to delete file with specified name
     * 
     * @apiSuccessExample {json} Success-Response:
     *     HTTP/1.1 200 OK
     *     {"status":"ok"}
     * 
     * @apiExample {curl} Example usage:
     *     curl --request DELETE 'http://localhost:1234/drive/v1/file?name=sample.jpg'
     */
    public function file_delete() {
        if (!$this->init_filestorage()) {
            return;
        }

        $name = trim($this->input->get('name'));
        $file = $this->File_model->get_by_name($name);
        if (!$file) {
            $this->return_error('not_found', 'file not found', REST_Controller::HTTP_NOT_FOUND);
            return;
        }

        $success = $this->File_model->delete($file);
        if (!$success) {
            $this->return_error('failed', 'failed to delete from database', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
            return;
        }

        $files_with_same_path = $this->File_model->get_by_path($file->get_path());
        if (!$files_with_same_path) {
            $success = $this->filestorage->delete($file->get_path());
            if (!$success) {
                $this->return_error('failed', 'failed to delete file', REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
                return;
            }
        }

        $this->return_ok(null, REST_Controller::HTTP_OK);
    }

    private function validate_name($name) {
        for ($i = 0; $i < strlen($name); $i++) {
            if (strpos(self::$whitelisted_characters, substr($name, $i, 1)) === false) {
                return false;
            }
        }

        return true;
    }

}
