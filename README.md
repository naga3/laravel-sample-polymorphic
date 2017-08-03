# はじめに

Laravelによるポリモーフィックリレーションのサンプルです。

![screen.gif](https://qiita-image-store.s3.amazonaws.com/0/32030/af3c6012-d9a7-8212-af20-edaf795041c0.gif)

ポリモーフィックリレーションとは、親が複数ある親子関係を表すときに使います。あまり日本語の文献がないので書き起こしてみました。

今回は投稿とコメントの両方とも画像を添付できる掲示板を作成します。画像のテーブルは投稿テーブルかコメントテーブルのどちらかが親になります。このような場合、ポリモーフィックリレーションを使うとシンプルに定義できます。

GitHub: https://github.com/naga3/laravel-sample-polymorphic

# プロジェクト作成

Laravelのプロジェクトを適当な名前で新規作成してください。

その後、ファイルストレージをウェブ上から見るためにシンボリックリンクを張ります。

```
php artisan storage:link
```

# モデル作成

投稿用のモデル`Post`、コメント用のモデル`Comment`、添付画像用のモデル`Image`を作成します。

```
php artisan make:model Post -m
php artisan make:model Comment -m
php artisan make:model Image -m
```

`-m`オプションでマイグレーションファイルも同時に作成します。

# テーブル作成

作成されたマイグレーションファイルを編集します。

## xxxx_create_posts_table.php

投稿テーブルです。`title`が題名、`body`が本文になります。

```php
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });
    }
```

## xxxx_create_comments_table.php

コメントテーブルです。`post_id`が投稿ID、`body`がコメント本文となります。

```php
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('post_id');
            $table->text('body');
            $table->timestamps();
        });
    }
```

## xxxx_create_images_table

添付画像テーブルです。`target_id`は画像が添付されている投稿IDまたはコメントID、`target_type`は画像が添付されているモデルのクラス名（`App\Post`または`App\Comment`）となります。このような形式にすることでポリモーフィックリレーションを設定しやすくしています。`filename`は画像のファイル名です。

```php
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('target_id');
            $table->string('target_type');
            $table->string('filename');
            $table->timestamps();
        });
    }
```

# リレーションの設定

モデルクラスにリレーションを設定します。

## Post.php

```php
class Post extends Model
{
    public function images()
    {
        return $this->morphMany(Image::class, 'target');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
```

`images`メソッド内の`morphMany`メソッドでポリモーフィックリレーションを設定しています。2番目の引数の`target`が重要で、これによって`target_id`が投稿ID、`target_type`の投稿モデルのクラス名となり、画像がどの投稿の添付であるかを判別します。

`comments`メソッド内の`hasMany`メソッドは1対多のリレーションです。投稿に紐付いているコメントを全て取得します。

## Comment.php

```php
class Comment extends Model
{
    public function images()
    {
        return $this->morphMany(Image::class, 'target');
    }
}
```

こちらも、`morphMany`メソッドでポリモーフィックリレーションを設定しています。`target_id`のコメントIDと`target_type`のコメントモデルのクラス名により、画像がどのコメントの添付であるかを判別します。

## Image.php

```php
class Image extends Model
{
    protected $guarded = ['id']; 
}
```

`Image`モデルのリレーションは設定する必要はありません。一括`create`するのでMass assignmentの設定だけしています。

# ルートの設定

`web.php`を以下のようにします。

```php
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
```

`// Post`のところが投稿部分です。

`$file->store`メソッドでファイルをストレージに保存しています。

`$post->images()->create`メソッドで、投稿に紐付いた画像レコードを生成しています。`hashName`は上記`store`メソッドで保存されたファイル名です。`target_id`と`target_type`は自動的に入ります。

`// Comment`のところがコメント部分です。投稿とほぼ同じ処理です。

# ビューの設定

ビューファイル`home.blade.php`を作成して以下のようにします。

```php
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
```

# おわりに

ポリモーフィックリレーションを使うことによって、親が複数ある親子関係をスッキリと記述することができました。
