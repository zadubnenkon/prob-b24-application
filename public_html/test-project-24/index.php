<?php

require __DIR__ . '/../../vendor/autoload.php';

use Lib\Bitrix24Apps\TestProject;

try {
    $result = (new TestProject)->init()->getCurrentUser();
} catch (\Exception $e) {
    $result = [
        'error' => $e->getCode(),
        'error_information' => $e->getMessage(),
    ];
}

echo '<pre>', json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), '</pre>';