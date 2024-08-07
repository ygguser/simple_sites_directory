<?php

define(
    'WEBSITE_URL',
    'http://[21e:a51c:885b:7db0:166e:927:98cd:d186]'
);

define(
    'WEBSITE_NAME',
    _('Web-sites directory')
);

define(
    'RSS_LIMIT',
    20
);

define(
    'DNS_DIG_TIME',
    2
);

define(
    'DNS_YGG',
    [
        '302:a4cb:8384:284d::53',  //closest (hoster)
        '302:db60::53', 
        '308:25:40:bd::',
        '308:62:45:62::',
        '301:1088::53',
        '300:4b63:bc3e:f090:babe::0',
        '303:71a7:ae08:b479::53',
        '301:23b4:991a:634d::53',
    ]
);

define(
    'DNS_EMERCOIN',
    [
        'seed1.emercoin.com',
    ]
);

define('DB_FILE', './../database.db');
