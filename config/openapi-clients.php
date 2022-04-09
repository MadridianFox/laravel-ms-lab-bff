<?php

return [
    'posts' => [
        'base_uri' => env('POSTS_SERVICE_HOST') . "/api/v1",
    ],
    'comments' => [
        'base_uri' => env('COMMENTS_SERVICE_HOST') . "/api/v1",
    ],
];
