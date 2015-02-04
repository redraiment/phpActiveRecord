<?php

require_once('db.php');
require_once('table.php');

$db = DB::open('mysql:host=127.0.0.1;dbname=zjt;', 'redraiment', '123456');
// $User = $db->createTable('users', 'name varchar(20)', 'age int');
// var_dump($User);
$db->dropTable('users');
