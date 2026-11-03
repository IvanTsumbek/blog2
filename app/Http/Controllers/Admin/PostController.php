<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|min:3',
            'slug' => 'nullable',
            'excerpt' => 'required|min:10',
            'body' => 'required|min:10',
            'is_published' => 'nullable',
            'published_at' => 'nullable',
            'user_id' => 'nullable',
        ]);

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

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|min:3',
            'excerpt' => 'required|min:10',
            'body' => 'required|min:10',
            'is_published' => 'nullable',
            'published_at' => 'nullable',
            'user_id' => 'nullable',
        ]);

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
