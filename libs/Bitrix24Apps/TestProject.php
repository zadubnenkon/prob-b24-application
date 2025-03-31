<?php

namespace Lib\Bitrix24Apps;

use Lib\bitrix24\ApiClient;
use Lib\Bitrix24\RestEntity\User;

class TestProject
{
    protected ApiClient $apiClient;

    public function init()
    {
        $post = $_POST ?: json_decode(file_get_contents("php://input") ?: '[]', true);

        if (!$post || !\key_exists('domain', $post)) {
            throw new \Exception('Param "domain" is required!', 400);
        }

        $env = parse_ini_file(__DIR__ . '/../../TestProject.env');

        $this->apiClient = new ApiClient($post['domain'], [
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