<?php

/*
 Copyright (C) 2016 - Ward Mundy, Sylvain Boily
 SPDX-License-Identifier: GPL-3.0+
*/


include_once("restclient.php");


class Wazo {

    function __construct($wazo_host) {
        $this->wazo_host = $wazo_host;
        $this->backend_user = "xivo_user";
        $this->wazo_session = isset($_COOKIE['wazo']['session']) ? $_COOKIE['wazo']['session'] : NULL;
        $this->wazo_uuid = $this->_get_uuid();
    }

    private function _connect($port, $version, $token=NULL, $login=NULL, $password=NULL) {
        $connect = new RestClient([
            'base_url' => "https://$this->wazo_host:$port/$version",
            'headers' => ['X-Auth-Token' => $token],
            'curl_options' => [CURLOPT_SSL_VERIFYPEER => false,
                               CURLOPT_SSL_VERIFYHOST => false,
                               CURLOPT_ENCODING => 'application/json',
                              ],
            'decoders' => ['json'],
            'username' => $login,
            'password' => $password

        ]);

        return $connect;
    }

    private function _get_uuid() {
        if (empty($this->wazo_session)) {
            return false;
        }

        $connect = $this->_connect(9497, "0.1", $this->wazo_session);
        $uuid = $connect->get("token/$this->wazo_session");

        if ($uuid->info->http_code == 200) {
            return json_decode($uuid->response)->data->wazo_user_uuid; 
        }

        return false;
    }

    private function _get_token($login, $password, $backend) {
        $auth_info = json_encode(['backend' => $backend,
                                  'expiration' => 3600
                                 ]);

        $connect = $this->_connect(9497, "0.1", NULL, $login, $password);
        $t = $connect->post("token", $auth_info, ['Content-Type' => 'application/json']);

        if ($t->info->http_code == 200) {
            $info['token'] = json_decode($t->response)->data->token;
            $info['uuid'] = json_decode($t->response)->data->wazo_user_uuid;

            return $info;
        }

        return false;
    }

    public function login($login, $password) {
        $info = $this->_get_token($login, $password, $this->backend_user);

        return $info['token'];
    }

    public function logout() {
        $connect = $this->_connect(9497, "0.1");
        $connect->delete("token/$this->wazo_session");

        setcookie("wazo[session]", "", time() - 3600);
        setcookie("wazo[uuid]", "", time() - 3600);

        header('Location: index.php');
    }

    public function list_users() {
        $connect = $this->_connect(9486, "1.1", $this->wazo_session);
        $users = $connect->get("users");

        if ($users->info->http_code == 200) {
            return json_decode($users->response);
        }
        return "Error to get users";
    }

    public function list_trunks() {
        $connect = $this->_connect(9486, "1.1", $this->wazo_session);
        $trunks = $connect->get("trunks");

        if ($trunks->info->http_code == 200) {
            return json_decode($trunks->response);
        }
        return "Error to get trunks";
    }

    public function list_lines() {
        $connect = $this->_connect(9486, "1.1", $this->wazo_session);
        $lines = $connect->get("lines");

        if ($lines->info->http_code == 200) {
            return json_decode($lines->response);
        }
        return "Error to get lines";
    }

    public function get_endpoint_sip($id) {
        $connect = $this->_connect(9486, "1.1", $this->wazo_session);
        $endpoint_sip = $connect->get("endpoints/sip/{$id}");

        if ($endpoint_sip->info->http_code == 200) {
            return json_decode($endpoint_sip->response);
        }
        return "Error to get endpoint_sip";
    }

    public function get_cdr() {
        $connect = $this->_connect(9486, "1.1", $this->wazo_session);
        $cdrs = $connect->get("call_logs");

        if ($cdrs->info->http_code == 200) {
            return $cdrs->response;
        }
        return "Error to get cdrs";
    }

}

?>
