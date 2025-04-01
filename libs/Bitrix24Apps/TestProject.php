<?php

namespace Lib\Bitrix24Apps;

use Lib\Bitrix24\ApiClient;
use Lib\Bitrix24\RestEntity\User;

class TestProject
{
    protected ApiClient $apiClient;

    public function init()
    {
        $post = $_POST ?: json_decode(file_get_contents("php://input") ?: '[]', true);

        if (!$post || !\key_exists('DOMAIN', $_REQUEST)) {
            throw new \Exception('Param "DOMAIN" is required!', 400);
        }

        $env = parse_ini_file(__DIR__ . '/../../TestProject.env');

        $this->apiClient = new ApiClient($_REQUEST['DOMAIN'], [
            'accessToken' => $post['AUTH_ID'],
            'refreshToken' => $post['REFRESH_ID'],
            'clientID' => $env['CLIENT_ID'],
            'clientSecret' => $env['CLIENT_SECRET'],
        ]);

        return $this;
    }

    public function getCurrentUser()
    {
        $result = (new User($this->apiClient))->getCurrent();

        if (\key_exists('error', $result)) {
            throw new \Exception($result['error_information'], $result['error_exception_code'] ?: 500);
        }

        return key_exists('result', $result) ? $result['result'] : $result;
    }
}