<?php

namespace App\Domain\Blog\Models;

use Lab\CommentsClient\Dto\Comment;
use Lab\PostsClient\Dto\PostDetail as PostDetailDto;


class PostDetail
{
    public PostDetailDto $post;
    /** @var Comment[]|null */
    public ?array $comments;
}
