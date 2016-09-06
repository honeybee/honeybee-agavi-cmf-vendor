<?php
// throw the exception so it shows up in error logs
if(!ini_get('display_errors')) {
    throw $e;
    // error_log and then return vnd.error+json document?
}
throw($e);
if(!headers_sent()) {
    header('Status: 500');
    header('Content-Type: application/hal+json');
}

$msg = 'An exception occurred.';
if (count($exceptions) > 1) {
    $msg .= sprintf(
        ' The %s was caused by %s. A full chain of exceptions is listed below.',
        get_class($e),
        ((count($exceptions) == 2) ? 'another exception' : 'other exceptions')
    );

}

$exc = [];
foreach ($exceptions as $ei => $e) {
    $exc[$ei]['exception_class'] = get_class($e);
    $exc[$ei]['exception_message'] = str_replace("\n", " ", trim($e->getMessage()));
}

$version_info = [
    "agavi" => AgaviConfig::get('agavi.version'),
    "php" => phpversion(),
    "system" => php_uname(),
    "timestamp" => gmdate(DATE_ISO8601),
];

$json = [
    'message' => $msg,
    'exceptions' => $exc,
    'version_info' => $version_info,
];

echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
