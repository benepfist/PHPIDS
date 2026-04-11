<?php
require_once __DIR__.'/../../../vendor/autoload.php';

$workspaceRoot = realpath(__DIR__ . '/../../..');
$defaultTempRoot = ($workspaceRoot !== false ? $workspaceRoot . DIRECTORY_SEPARATOR . 'tmp' : sys_get_temp_dir());
$runtimeTempRoot = null;

$config = array();
foreach ($GLOBALS as $name => $value) {
    if (strpos($name, 'IDS_') !== 0) {
        continue;
    }

    /** Allow environment override */
    if (isset($_SERVER[$name])) {
        $value = $_SERVER[$name];
    }

    /** Make absolute path */
    if (substr($value, 0, 4) === 'lib/') {
        $value = realpath(__DIR__ . '/../../..') . '/' . $value;
    }

    if ($name == 'IDS_TEMP_DIR') {
        $candidate = $value;
        if (!is_dir($candidate) || !is_writable($candidate)) {
            $candidate = $defaultTempRoot;
        }

        if (!is_dir($candidate)) {
            mkdir($candidate, 0777, true);
        }

        $runtimeTempRoot = $candidate . DIRECTORY_SEPARATOR . 'IDS_' . microtime(true);
        mkdir($runtimeTempRoot, 0777, true);
        $value = $runtimeTempRoot;
    }

    if ($name == 'IDS_FILTER_CACHE_FILE') {
        $directory = dirname($value);
        if ($runtimeTempRoot !== null) {
            $value = $runtimeTempRoot . DIRECTORY_SEPARATOR . basename($value);
        } elseif (!is_dir($directory) || !is_writable($directory)) {
            $value = $defaultTempRoot . DIRECTORY_SEPARATOR . basename($value);
        }
    }

    define($name, $value);
    $config[$name] = $value;
}

$configInfo = <<<EOS
PHPIDS TestSuite configuration:

Filter type:            IDS_FILTER_TYPE
Filter set:             IDS_FILTER_SET
Temporary directory:    IDS_TEMP_DIR
Configuration:          IDS_CONFIG


EOS;

echo str_replace(array_keys($config), array_values($config), $configInfo);
