<?php

namespace App\Domain\Blog\Actions;

use App\Domain\Blog\Models\PostDetail;
use GuzzleHttp\Promise\Utils;
use Lab\CommentsClient\Api\CommentsApi;
use Lab\CommentsClient\Dto\CommentListResponse;
use Lab\PostsClient\Api\PostsApi;
use Lab\PostsClient\Dto\PostDetailResponse;

class LoadPostDetailAction
{
    public function __construct(
        protected PostsApi $postsApi,
        protected CommentsApi $commentsApi,
    ) {
    }

    public function execute(string $code): ?PostDetail
    {
        $promises[] = $this->postsApi->detailAsync($code)
            ->otherwise(fn () => null);
        $promises[] = $this->commentsApi->listByPostAsync($code)
            ->otherwise(fn () => null);
        /**
         * @var PostDetailResponse $postsResponse
         * @var CommentListResponse $commentsResponse
         */
        [$postsResponse, $commentsResponse] = Utils::unwrap($promises);

        if (!$postsResponse) {
            return null;
        }

        $post = new PostDetail();
        $post->post = $postsResponse->getData();
        $post->comments = $commentsResponse?->getData();

        return $post;
    }
}
