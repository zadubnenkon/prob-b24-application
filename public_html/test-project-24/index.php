<?php

require __DIR__ . '/../../vendor/autoload.php';

use Lib\Bitrix24Apps\TestProject;

$result = (new TestProject)->init()->getCurrentUser();

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);