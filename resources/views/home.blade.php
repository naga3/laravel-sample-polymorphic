<!doctype html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <title>BBS</title>
</head>
<body>
    <form method="post" enctype="multipart/form-data">
        {{ csrf_field() }}
        <p>題名<br><input type="text" name="title" size="50"></p>
        <p>本文<br><textarea name="body" cols="50" rows="8"></textarea></p>
        <p>添付<br><input type="file" name="files[]" multiple></p>
        <button>投稿</button>
    </form>
    @foreach ($posts as $post)
        <hr>
        <p>{{ $post->title }}</p>
        <pre>{{ $post->body }}</pre>
        <p>
            @foreach ($post->images as $image)
                <img src="{{ 'storage/' . $image->filename }}">
            @endforeach
        </p>
        <blockquote>
            @foreach ($post->comments as $comment)
                <pre>{{ $comment->body }}</pre>
                <p>
                    @foreach ($comment->images as $image)
                        <img src="{{ 'storage/' . $image->filename }}">
                    @endforeach
                </p>
            @endforeach
            <form method="post" action="{{ url('/comments') }}" enctype="multipart/form-data">
                {{ csrf_field() }}
                <p>コメント<br><textarea name="body" cols="50" rows="3"></textarea></p>
                <p>添付<br><input type="file" name="files[]" multiple></p>
                <input type="hidden" name="post_id" value="{{ $post->id }}">
                <button>コメント投稿</button>
            </form>
        </blockquote>
    @endforeach
</body>
</html>
