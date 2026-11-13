<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
// use App\Models\Post;
use App\Services\Interfaces\PostServiceInterface;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct(
        private PostServiceInterface $postService
    ) {}

    public function index(): JsonResponse
    {
        return response()->json($this->postService->getAllApi());
    }

    public function show(int $id): JsonResponse
    {
        $post = $this->postService->getByIdApi($id);

        if (!$post) return response()->json(['message' => 'Not Found'], 404);

        return response()->json($post);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        return response()->json($this->postService->createApi($data), 201);
    }

    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $post = $this->postService->updateApi($id, $request->validated());

        if (!$post) return response()->json(['message' => 'Not Found'], 404);

        return response()->json($post);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->postService->deleteApi($id);

        if (!$deleted) return response()->json(['message' => 'Not Found'], 404);

        return response()->json(['message' => 'Deleted']);
    }

    // public function index(): JsonResponse
    // {
    //     $posts = Post::query()->latest()->paginate(10);

    //     return response()->json([
    //         'data' => $posts->items(),
    //         'meta' => [
    //             'current_page' => $posts->currentPage(),
    //             'last_page'    => $posts->lastPage(),
    //             'per_page'     => $posts->perPage(),
    //             'total'        => $posts->total(),
    //         ]
    //     ]);
    // }

    // public function show(int $id): JsonResponse
    // {
    //     $post = Post::query()->find($id);

    //     if (!$post) {
    //         return response()->json([
    //             'message' => 'Пост не найден'
    //         ], 404);
    //     }

    //     return response()->json([
    //         'data' => $post
    //     ]);
    // }
}
