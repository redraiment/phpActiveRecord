<?php

require_once('activerecord.php');

$db = DB::open('pgsql:host=127.0.0.1;dbname=zombie;', 'dba', 'xticfeeq');

// Tables
$Zombie = $db->createTable('zombies',
                           'name varchar(64)'
);
$Zombie->purge();
$City = $db->createTable('cities',
                         'name varchar(64)'
);
$City->purge();
$Tweet = $db->createTable('tweets',
                          'zombie_id integer',
                          'city_id integer',
                          'content varchar(128)'
);
$Tweet->purge();
$Comment = $db->createTable('comments',
                            'zombie_id integer',
                            'tweet_id integer',
                            'content varchar(128)'
);
$Comment->purge();
$Relation = $db->createTable('relations',
                             'following integer',
                             'follower integer'
);
$Relation->purge();

// Relations
$Zombie->hasMany('tweets')->by('zombie_id');
$Zombie->hasAndBelongsToMany('travelled_cities')->by('city_id')->in('cities')->through('tweets');
$Zombie->hasMany("received_comments")->by("tweet_id")->in("comments")->through("tweets");
$Zombie->hasMany("send_comments")->by("zombie_id")->in("comments");
$Zombie->hasMany("follower_relations")->by("following")->in("relations");
$Zombie->hasAndBelongsToMany("followers")->by("follower")->in("zombies")->through("follower_relations");
$Zombie->hasMany("following_relations")->by("follower")->in("relations");
$Zombie->hasAndBelongsToMany("followings")->by("following")->in("zombies")->through("following_relations");

$City->hasMany("tweets")->by("city_id");
$City->hasAndBelongsToMany("zombies")->by("zombie_id")->through("tweets");

$Tweet->belongsTo("zombie")->in("zombies");
$Tweet->belongsTo("city")->in("cities");
$Tweet->hasMany("comments")->by("tweet_id");

$Comment->belongsTo("zombie")->by("zombie_id")->in("zombies");
$Comment->belongsTo("tweet")->by("tweet_id")->in("tweets");

// meta data
$boston = $City->create("name:", "Boston");
$newyord = $City->create("name:", "NewYork");

$ash = $Zombie->create("name:", "Ash");
$ashTweets = $ash->tweets;
$ashTweetOnBoston = $ashTweets->create("city_id:", $boston->id, "content:", "Hello Boston from Ash!")->comments;
$ashTweetOnNewYork = $ashTweets->create("city_id:", $newyord->id, "content:", "Hello NewYord from Ash!")->comments;

$bob = $Zombie->create("name:", "Bob");
$bobTweets = $bob->tweets;
$bobTweetOnBoston = $bobTweets->create("city_id:", $boston->id, "content:", "Hello Boston from Bob!")->comments;
$bobTweetOnNewYork = $bobTweets->create("city_id:", $newyord->id, "content:", "Hello NewYord from Bob!")->comments;

$jim = $Zombie->create("name:", "Jim");
$jimTweets = $jim->tweets;
$jimTweetOnBoston = $jimTweets->create("city_id:", $boston->id, "content:", "Hello Boston from Jim!")->comments;
$jimTweetOnNewYork = $jimTweets->create("city_id:", $newyord->id, "content:", "Hello NewYord from Jim!")->comments;

$ashTweetOnBoston->create("zombie_id:", $bob->id, "content:", "Cool from Bob @ Boston");
$ashTweetOnBoston->create("zombie_id:", $jim->id, "content:", "Cool from Jim @ Boston");
$ashTweetOnNewYork->create("zombie_id:", $bob->id, "content:", "Cool from Bob @ NewYork");
$ashTweetOnNewYork->create("zombie_id:", $jim->id, "content:", "Cool from Jim @ NewYork");
$bobTweetOnBoston->create("zombie_id:", $ash->id, "content:", "Cool from Ash @ Boston");
$bobTweetOnBoston->create("zombie_id:", $jim->id, "content:", "Cool from Jim @ Boston");
$bobTweetOnNewYork->create("zombie_id:", $ash->id, "content:", "Cool from Ash @ NewYork");
$bobTweetOnNewYork->create("zombie_id:", $jim->id, "content:", "Cool from Jim @ NewYork");
$jimTweetOnBoston->create("zombie_id:", $ash->id, "content:", "Cool from Ash @ Boston");
$jimTweetOnBoston->create("zombie_id:", $bob->id, "content:", "Cool from Bob @ Boston");
$jimTweetOnNewYork->create("zombie_id:", $ash->id, "content:", "Cool from Ash @ NewYork");
$jimTweetOnNewYork->create("zombie_id:", $bob->id, "content:", "Cool from Bob @ NewYork");

$Relation->create("following:", $ash->id, "follower:", $bob->id);
$Relation->create("following:", $ash->id, "follower:", $jim->id);
$Relation->create("following:", $bob->id, "follower:", $ash->id);
$Relation->create("following:", $bob->id, "follower:", $bob->id);
$Relation->create("following:", $jim->id, "follower:", $ash->id);
$Relation->create("following:", $jim->id, "follower:", $jim->id);
