<?php

namespace App\Services;

use App\Models\Post;
use App\Repositories\Interfaces\PostRepositoryInterface;
use App\Services\Interfaces\PostServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostService implements PostServiceInterface
{
    public function __construct(
        private PostRepositoryInterface $postRepository
    ) {}

    public function getAllApi(): Collection
    {
        $posts = Cache::remember('posts_all', 120, function () {
            return $this->postRepository->allApi()->toArray();
        });

        return Post::hydrate($posts);
    }

    public function getTrashed(): Collection
    {
        return Cache::remember('posts_trash', 120, function () {
            return $this->postRepository->getTrashed();
        });
    }

    public function getByIdApi(int $id): ?Post
    {
        $data = Cache::remember("post_{$id}", 60, function () use ($id) {
            return $this->postRepository->findApi($id)?->toArray();
        });

        return $data ? (new Post())->newFromBuilder($data) : null;
    }

    public function createApi(array $data): Post
    {
        $slug = Str::slug($data['title']);
        $data['slug'] = $slug;
        $post = $this->postRepository->createApi($data);

        Cache::forget('posts_all');

        $id = $post->id;

        // Cache::tags(['posts'])->flush();

        return Cache::tags(['posts'])->remember("post_{$id}", 120, function () use ($id) {
            return $this->postRepository->findApi($id);
        });
    }

    public function updateApi(int $id, array $data): ?Post
    {
        $post = $this->postRepository->findApi($id);
        if (!$post) return null;

        $slug = Str::slug($data['title']);
        $data['slug'] = $slug;
        $updatedPost = $this->postRepository->updateApi($post, $data);

        Cache::forget('posts_all');
        return $updatedPost;
    }

    public function softDeleteApi(int $id): bool
    {
        $post = $this->postRepository->findApi($id);
        if (!$post) return false;

        $deletedPost = $this->postRepository->softDelete($post);
        Cache::forget('posts_all');
        Cache::forget("posts_{$id}");

        return $deletedPost;
    }

    public function restoreApi(int $id): ?Post
    {
        $post = $this->postRepository->restore($id);
        if (!$post) return null;

        Cache::forget('posts_all');
        Cache::forget("posts_{$id}");

        return $post;
    }

    public function forceDeleteApi(int $id): bool
    {
        $deleted = $this->postRepository->forceDelete($id);
        if (!$deleted) return false;

        Cache::forget('posts_all');
        Cache::forget("posts_{$id}");

        return true;
    }

    public function create(array $data): Post
    {
        return DB::transaction(function () use ($data) {
            $image = $data['image'] ?? null;
            unset($data['image'], $data['remove_image']);

            $slugBase = Str::slug($data['title']);
            $slug = $slugBase . '-' . rand(1, 999999);
            $data['slug'] = $slug;

            $isPublished = (bool)($data['is_published'] ?? false);
            $data['is_published'] = $isPublished;
            $data['published_at'] = $isPublished ? now() : null;

            $post = Post::create($data);

            if ($image) {
                $path = $image->store('posts', 'public');
                $post->image = $path;
                $post->save();
            }

            return $post;
        });
    }

    public function update(Post $post, array $data): Post
    {
        return DB::transaction(function () use ($post, $data) {
            $newImage = $data['image'] ?? null;
            $removeImage = (bool)($data['remove_image'] ?? false);
            unset($data['image'], $data['remove_image']);

            $wasPublished = (bool)$post->is_published;
            $nowPublished = (bool)($data['is_published'] ?? $wasPublished);
            $data['is_published'] = $nowPublished;

            if (!$wasPublished && $nowPublished) {
                $data['published_at'] = now();
            } elseif ($wasPublished && !$nowPublished) {
                $data['published_at'] = null;
            }

            $slugBase = Str::slug($data['title']);
            $slug = $slugBase . '-' . rand(1, 999999);
            $data['slug'] = $slug;

            $post->update($data);

            if ($removeImage && $post->image) {
                Storage::disk('public')->delete($post->image);
                $post->image = null;
            }

            if ($newImage) {
                if ($post->image) {
                    Storage::disk('public')->delete($post->image);
                }
                $post->image = $newImage->store('posts', 'public');
            }

            $post->save();

            return $post;
        });
    }

    public function delete(Post $post): void
    {
        DB::transaction(function () use ($post) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $post->delete();
        });
    }
}
