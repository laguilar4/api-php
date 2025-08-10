<?php
header('Content-Type: text/plain');

$headers = getallheaders();
foreach ($headers as $name => $value) {
    echo "$name: $value\n";
}

echo "\n\nHTTP_AUTHORIZATION: " . ($_SERVER['HTTP_AUTHORIZATION'] ?? "No está seteado");
