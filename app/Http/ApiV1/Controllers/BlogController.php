<?php

namespace App\Http\ApiV1\Controllers;

use App\Domain\Blog\Actions\LoadPostDetailAction;
use App\Http\ApiV1\Resources\PostDetailResource;
use Illuminate\Contracts\Support\Responsable;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BlogController
{
    public function postDetail(string $code, LoadPostDetailAction $action): Responsable
    {
        $post = $action->execute($code);
        if (!$post) {
            throw new NotFoundHttpException("Post not found");
        }

        return PostDetailResource::make($post);
    }
}
