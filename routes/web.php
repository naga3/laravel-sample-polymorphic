<?php
Route::get('/', function () {
    return view('home', ['posts' => App\Post::orderBy('id', 'desc')->get()]);
});

// Post
Route::post('/', function () {
    $post = new App\Post();
    $post->title = request('title');
    $post->body = request('body');
    $post->save();

    $files = request('files');
    if ($files) foreach ($files as $file) {
        $file->store('public');
        $post->images()->create(['filename' => $file->hashName()]);
    }
    return redirect('/');
});

// Comment
Route::post('/comments', function () {
    $comment = new App\Comment();
    $comment->post_id = request('post_id');
    $comment->body = request('body');
    $comment->save();

    $files = request('files');
    if ($files) foreach ($files as $file) {
        $file->store('public');
        $comment->images()->create(['filename' => $file->hashName()]);
    }
    return redirect('/');
});
