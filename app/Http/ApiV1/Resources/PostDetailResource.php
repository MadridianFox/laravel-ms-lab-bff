<?php

namespace App\Http\ApiV1\Resources;

use App\Domain\Blog\Models\PostDetail;
use App\Http\ApiV1\Support\Resources\BaseJsonResource;
use Lab\CommentsClient\Dto\Comment;
use Lab\PostsClient\Dto\Tag;

/**
 * @mixin PostDetail
 */
class PostDetailResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'code' => $this->post->getCode(),
            'title' => $this->post->getTitle(),
            'text' => $this->post->getText(),
            'author' => $this->post->getAuthor(),
            'author_id' => $this->post->getAuthorId(),
            'created_at' => $this->post->getCreatedAt(),
            'tags' => array_map(function (Tag $tag) {
                return [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                ];
            }, $this->post->getTags()),
            'comments' => array_map(function (Comment $comment) {
                return [
                    'id' => $comment->getId(),
                    'text' => $comment->getText(),
                    'author' => $comment->getAuthor(),
                    'author_id' => $comment->getAuthorId(),
                    'created_at' => $comment->getCreatedAt(),
                ];
            }, $this->comments ?? []),
        ];
    }
}
