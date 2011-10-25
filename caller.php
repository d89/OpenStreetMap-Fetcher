<?php
header('content-type: text/html; charset=utf-8');

$request = array
(
    "lat" => 49.870618,
    "long" => 8.641756,
    "action" => "food,party"
);

echo "<h1>Request</h1>";
$server = $_SERVER['HTTP_HOST'];
$is_live = $server == "localhost";
$url = "http://" . $server . ($is_live ? "/freetime/" : "/") . "?" . urldecode(http_build_query($request));
echo $url;

echo "<h1>Response</h1>";
$result = file_get_contents($url);
echo "<pre>";
print_r(json_decode($result, true));
echo "</pre>";
