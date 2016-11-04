<?php

defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . '/libraries/REST_Controller.php';

class MY_Controller extends REST_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('migration');
        $this->migration->latest();
    }

    protected function return_error($message, $http_code = REST_Controller::HTTP_BAD_REQUEST) {
        $this->set_response([
            'status' => 'error',
            'message' => $message
        ], $http_code);
    }

    protected function return_ok($data, $http_code = REST_Controller::HTTP_OK) {
        $to_return = ['status' => 'ok'];
        if ($data) {
            $to_return['data'] = $data;
        }
        $this->set_response($to_return, $http_code);
    }

}
