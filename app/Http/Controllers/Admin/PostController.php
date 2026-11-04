<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::latest()->paginate(5);

        return view('admin.posts.index', compact('posts'));
    }

    public function create(): View
    {
        return view('admin.posts.create');
    }

    public function store(StorePostRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $slugBase = Str::slug($data['title']);
        $slug = $slugBase . '-' . rand(1, 999999);

        $data['slug'] = $slug;

        $data['is_published'] = $request->has('is_published') == 'on';
        $data['published_at'] = $request->has('is_published') ? now() : null;
        Post::create($data);

        return redirect()->route('admin.posts.index')->with('success', 'Post created successfully!');
    }

    public function show(Post $post): View
    {
        return view('admin.posts.show', compact('post'));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.edit', compact('post'));
    }

    public function update(UpdatePostRequest $request, Post $post): RedirectResponse
    {
        $data = $request->validated();

        if ($request->boolean('remove_image') && $post->image) {
            Storage::disk('public')->delete($post->image);
            $data['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($post->image) {
                Storage::disk('public')->delete($post->image);
            }
            $data['image'] = $request->file('image')->store('posts', 'public');
        }

        $data['is_published'] = $request->has('is_published') == 'on';
        $data['published_at'] = $request->has('is_published') ? now() : null;

        $post->update($data);

        return redirect()->route('admin.posts.index')->with('success', 'Post updated successfully!');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.posts.index')->with('success', 'Post deleted successfully!');
    }
}
