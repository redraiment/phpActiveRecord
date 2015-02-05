<?php

require_once('activerecord.php');

$db = DB::open('sqlite::memory:');
$User = $db->createTable('users',
                         'name text',
                         'age integer'
);
$Tweet = $db->createTable('tweets',
                          'user_id integer',
                          'content text'
);
$User->hasMany('tweets');
$redraiment = $User->create('name:', 'redraiment',
                            'age:', 26
);
$redraiment->tweets->create('content:', 'hello world');
