<?php

namespace Lib\Bitrix24;

class ApiClient
{
    const BATCH_COUNT    = 50;
    const MAX_AUTH_ATTEMPT = 5;
    const TYPE_TRANSPORT = 'json'; // json or xml
    const IGNORE_SSL = false;

    const LOG_AVAILABLE = false;
    const LOG_DIR = __DIR__ . '/logs/';
    const LOG_VAR_EXPORT = false;

    protected ?string $domain;
    protected ?string $clientEndpoint;
    protected bool $isWebhook = false;

    protected ?string $accessToken;
    protected ?string $refreshToken;
    protected ?string $clientID;
    protected ?string $clientSecret;

    protected int $authAttempt = 0;

    /**
     * @param string $domain
     * @param array $authParams Keys: 'accessToken', 'refreshToken', 'clientID', 'clientSecret'
     */
    function __construct(string $domain, array $authParams = [])
    {
        if (\stripos($domain, '://')) {
            $domain = \explode('://', $domain)[1];
        }

        $this->$domain = \trim($domain, '/');
        $this->clientEndpoint = 'https://' . $this->$domain . '/';

        $this->accessToken = key_exists('accessToken', $authParams) ? $authParams['accessToken'] : '';
        $this->refreshToken = key_exists('refreshToken', $authParams) ? $authParams['refreshToken'] : '';
        $this->refreshToken = key_exists('clientID', $authParams) ? $authParams['clientID'] : '';
        $this->refreshToken = key_exists('clientSecret', $authParams) ? $authParams['clientSecret'] : '';

        if ($this->accessToken) {
            $this->clientEndpoint .= 'rest/';
        } else {
            $this->isWebhook = true;
        }
    }

    private function getNewAuth()
    {
        $result = [];

        if ($this->refreshToken) {
            $arParamsAuth = [
                'isAuth' => 'Y',
                'params'    =>
                [
                    'client_id'     => $this->clientID,
                    'grant_type'    => 'refresh_token',
                    'client_secret' => $this->clientSecret,
                    'refresh_token' => $this->refreshToken,
                ]
            ];

            $result = static::callCurl($arParamsAuth);

            if (key_exists('access_token', $result)) {
                $this->accessToken = $result['access_token'];
                $this->refreshToken = $result['refresh_token'];

                return [
                    "access_token" => $result["access_token"],
                    "refresh_token" => $result["refresh_token"],
                ];
            }
        }

        return $result;
    }

    /**
     * @param $method string
     * @param $params array method params
     * @return mixed array|string|boolean curl-return or error
     */

    public function call($method, $params = []): array | string
    {
        $result = $this->callCurl([
            'method' => $method,
            'params' => $params
        ]);

        return $result ?: [];
    }

    /**
     * @param $arParams array
     * $arParams = [
     *      'method' => 'b24 method',
     *      'params' => []
     * ];
     * @return mixed array|string|boolean
     */

    protected function callCurl($arParams)
    {
        if (!function_exists('curl_init')) {
            return $this->returnCurl([
                'error'             => 'error_php_lib_curl',
                'error_information' => 'need install curl lib'
            ]);
        }

        if (isset($arParams['isAuth']) && $arParams['isAuth'] == 'Y') {
            $url = 'https://oauth.bitrix.info/oauth/token/';
        } else {
            $url = $this->clientEndpoint . $arParams['method'] . '.' . static::TYPE_TRANSPORT;
            if (!$this->isWebhook) {
                $arParams['params']['auth'] = $this->accessToken;
            }
        }

        try {
            $obCurl = curl_init();

            curl_setopt($obCurl, CURLOPT_URL, $url);
            curl_setopt($obCurl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($obCurl, CURLOPT_POSTREDIR, 10);

            if ($sPostFields = http_build_query($arParams['params'])) {
                curl_setopt($obCurl, CURLOPT_POST, true);
                curl_setopt($obCurl, CURLOPT_POSTFIELDS, $sPostFields);
            }
            curl_setopt(
                $obCurl,
                CURLOPT_FOLLOWLOCATION,
                (isset($arParams['followlocation']))
                    ? $arParams['followlocation'] : 1
            );

            if (static::IGNORE_SSL === true) {
                curl_setopt($obCurl, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($obCurl, CURLOPT_SSL_VERIFYHOST, false);
            }

            $out = curl_exec($obCurl);
            $info = curl_getinfo($obCurl);
            curl_close($obCurl);

            if (curl_errno($obCurl)) {
                $info['curl_error'] = curl_error($obCurl);

                return $this->returnCurl([
                    'error'             => 'curl_error',
                    'error_information' => $info['curl_error']
                ]);
                
            }

            if (static::TYPE_TRANSPORT == 'xml' && (!isset($arParams['isAuth']) || $arParams['isAuth'] != 'Y')) {
                $result = $out;
            } else {
                $result = json_decode($out, true);
            }

            if (!empty($result['error'])) {
                if ($result['error'] === 'expired_token' && empty($arParams['isAuth'])) {
                    if ($this->authAttempt <= static::MAX_AUTH_ATTEMPT && static::GetNewAuth($arParams)) {
                        $this->authAttempt++;
                        $result = $this->callCurl($arParams);
                    }
                } else {
                    $arErrorInform = [
                        'expired_token'          => 'expired token, cant get new auth? Check access oauth server.',
                        'invalid_token'          => 'invalid token, need reinstall application',
                        'invalid_grant'          => 'invalid grant, check out define "clientSecret" or "clientID"',
                        'invalid_client'         => 'invalid client, check out define "clientSecret" or "clientID"',
                        'QUERY_LIMIT_EXCEEDED'   => 'Too many requests, maximum 2 query by second',
                        'ERROR_METHOD_NOT_FOUND' => 'Method not found! You can see the permissions of the application: $this->call(\'scope\')',
                        'NO_AUTH_FOUND'          => 'Some setup error b24, check in table "b_module_to_module" event "OnRestCheckAuth"',
                        'INTERNAL_SERVER_ERROR'  => 'Server down, try later'
                    ];
                    if (!empty($arErrorInform[$result['error']])) {
                        $result['error_information'] = $arErrorInform[$result['error']];
                    }
                }
            }

            static::setLog(
                [
                    'url'    => $url,
                    'info'   => $info,
                    'params' => $arParams,
                    'result' => $result
                ],
                'callCurl'
            );

            return $this->returnCurl($result);
        } catch (\Exception $e) {
            static::setLog(
                [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'trace' => $e->getTrace(),
                    'params' => $arParams
                ],
                'exceptionCurl'
            );

            return $this->returnCurl([
                'error' => 'exception',
                'error_exception_code' => $e->getCode(),
                'error_information' => $e->getMessage(),
            ]);
        }

        return $this->returnCurl([
            'error'             => 'no_install_app',
            'error_information' => 'error install app, pls install local application'
        ]);
    }

    protected function returnCurl($result)
    {
        $this->authAttempt = 0;
        return $result;
    }

    public static function callBatch($arData, $halt = 0)
    {
        $arResult = [];
        if (is_array($arData)) {
            $arDataRest = [];
            $i = 0;
            foreach ($arData as $key => $data) {
                if (!empty($data['method'])) {
                    $i++;
                    if (static::BATCH_COUNT >= $i) {
                        $arDataRest['cmd'][$key] = $data['method'];
                        if (!empty($data['params'])) {
                            $arDataRest['cmd'][$key] .= '?' . http_build_query($data['params']);
                        }
                    }
                }
            }
            if (!empty($arDataRest)) {
                $arDataRest['halt'] = $halt;
                $arPost = [
                    'method' => 'batch',
                    'params' => $arDataRest
                ];
                $arResult = static::callCurl($arPost);
            }
        }
        return $arResult;
    }


    /**
     * @var $data mixed
     * @var $debag boolean
     *
     * @return string json_encode with encoding
     */
    protected static function wrapData($data, $debag = false)
    {
        $return = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        if ($debag) {
            $e = json_last_error();
            if ($e != JSON_ERROR_NONE) {
                if ($e == JSON_ERROR_UTF8) {
                    return 'Failed encoding! Recommended \'UTF - 8\'';
                }
            }
        }

        return $return;
    }

    /**
     * Can overridden this method to change the log data storage location.
     *
     * @var $arData array of logs data
     * @var $type   string to more identification log data
     * @return boolean is successes save log data
     */

    public static function setLog($arData, $type = '')
    {
        $return = false;
        if (static::LOG_AVAILABLE !== true) {
            $path = static::LOG_DIR ?: __DIR__ . '/logs/';
            $path .= date("Y-m-d/H") . '/';

            if (!file_exists($path)) {
                @mkdir($path, 0775, true);
            }

            $path .= time() . '_' . $type . '_' . rand(1, 9999999) . 'log';

            if (static::LOG_VAR_EXPORT === true) {
                $return = file_put_contents($path . '.txt', var_export($arData, true));
            } else {
                if (false === $jsonLog = static::wrapData($arData)) {
                    $return = file_put_contents($path . '_backup.txt', var_export($arData, true));
                } else {
                    $return = file_put_contents($path . '.json', $jsonLog);
                }
            }
        }
        return $return;
    }
}
