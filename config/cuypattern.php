<?php

return [
    'enable_show_all' => true,
    'default_limit' => 10,
    'request_filter' => [
        'limit' => '_limit',
        'page' => '_page',
        'min' => '_min',
        'max' => '_max',
        'like' => '_like',
        'search' => '_search',
        'search_relation' => '_search_relation',
        'order' => '_order',
    ],
];
