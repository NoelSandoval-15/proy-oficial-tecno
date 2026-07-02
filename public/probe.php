<?php

header('Content-Type: text/plain');

echo "PROBE OK\n";
echo "METHOD=".$_SERVER['REQUEST_METHOD']."\n";
echo "REQUEST_URI=".($_SERVER['REQUEST_URI'] ?? '')."\n";
echo "SCRIPT_NAME=".($_SERVER['SCRIPT_NAME'] ?? '')."\n";
echo "PHP_SELF=".($_SERVER['PHP_SELF'] ?? '')."\n";
echo "DOCUMENT_ROOT=".($_SERVER['DOCUMENT_ROOT'] ?? '')."\n";
echo "SCRIPT_FILENAME=".($_SERVER['SCRIPT_FILENAME'] ?? '')."\n";
echo "__DIR__=".__DIR__."\n";
