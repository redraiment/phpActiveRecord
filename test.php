<?php

require_once('activerecord.php');

$db = DB::open('mysql:host=127.0.0.1;', 'root', 'xticfeeq');
$db->create('test');
$User = $db->createTable('users',
                         'name text',
                         'age integer'
);
$Tweet = $db->createTable('tweets',
                          'user_id integer',
                          'content text'
);
$User->hasMany('tweets')->by('user_id');
$Tweet->belongsTo('user')->in('users');
$redraiment = $User->create('name:', 'redraiment',
                            'age:', 26
);
$redraiment->tweets->create('content:', 'hello world');
