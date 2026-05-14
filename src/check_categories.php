<?php
require 'rb-postgres.php';
R::setup('pgsql:host=db;dbname=mydatabase', 'myuser', 'mypassword');
$cats = R::findAll('category');
foreach($cats as $c) {
    echo $c->name . ' (' . $c->slug . '): ' . $c->color . PHP_EOL;
}
?>