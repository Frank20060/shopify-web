<?php
require 'rb-postgres.php';
R::setup('pgsql:host=db;dbname=mydatabase', 'myuser', 'mypassword');
echo 'Total módulos: ' . R::count('module') . PHP_EOL;
echo 'Total categorías: ' . R::count('category') . PHP_EOL;
?>