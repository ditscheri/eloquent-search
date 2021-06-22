<?php

namespace Ditscheri\EloquentSearch\Tests;

use Ditscheri\EloquentSearch\Searchable;
use Illuminate\Database\Eloquent\Model;

class EloquentSearchGrammarTest extends TestCase
{
    /** @test */
    public function it_can_search_in_local_columns()
    {
        $builder = User::search('foo');

        $innerSelect = '
        select `matches`.`id` from (
            select `users`.`id` as `id` from `users`
            where (`users`.`name` like ? or `users`.`email` like ?)
        ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                   {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            ['foo%', 'foo%'],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_search_multiple_words_in_local_columns()
    {
        $builder = User::search('foo bar');

        $innerSelect = '
        select `matches`.`id` from (
            select `users`.`id` as `id` from `users`
            where (`users`.`name` like ? or `users`.`email` like ?)
        ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                    {$innerSelect}
                )
                and `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            ['foo%', 'foo%', 'bar%', 'bar%'],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_manipulate_columns()
    {
        $builder = User::search('foo', ['company']);

        $innerSelect = '
        select `matches`.`id` from (
            select `users`.`id` as `id` from `users`
            where (`users`.`company` like ?)
        ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            ['foo%'],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_search_in_one_foreign_column()
    {
        $builder = User::search('foo', [
            'posts.title',
        ]);

        $innerSelect = '
        select `matches`.`id` from (
            select `users`.`id` as `id` from `users`
            where exists (
                select 1 from `posts`
                where `users`.`id` = `posts`.`user_id` and (`posts`.`title` like ?)
            )
         ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            ['foo%'],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_search_in_many_foreign_column()
    {
        $builder = User::search('foo bar', [
            'posts.title',
            'posts.excerpt',
        ]);

        $innerSelect = '
        select `matches`.`id` from (
            select `users`.`id` as `id` from `users`
            where exists (
                select 1 from `posts`
                where `users`.`id` = `posts`.`user_id` and (`posts`.`title` like ? or `posts`.`excerpt` like ?)
            )
        ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                    {$innerSelect}
                )
                and `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            ['foo%', 'foo%', 'bar%', 'bar%'],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_search_combined()
    {
        $builder = User::search('foo bar', [
            'name',
            'email',
            'posts.title',
            'posts.excerpt',
        ]);

        $innerSelect = '
        select `matches`.`id` from (
            (
                select `users`.`id` as `id` from `users`
                where (`users`.`name` like ? or `users`.`email` like ?)
            )
            union
            (
                select `users`.`id` as `id` from `users`
                where exists (
                    select 1 from `posts`
                    where `users`.`id` = `posts`.`user_id`
                    and (`posts`.`title` like ? or `posts`.`excerpt` like ?)
                )
            )
        ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                    {$innerSelect}
                )
                and `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            [
                'foo%', 'foo%', 'foo%', 'foo%',
                'bar%', 'bar%', 'bar%', 'bar%',
            ],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_search_nested_relations()
    {
        $builder = User::search('foo bar', [
            'posts.comments.author.name',
        ]);

        $innerSelect = '
        select `matches`.`id` from (
            select `users`.`id` as `id` from `users`
            where exists (
                select * from `posts`
                where `users`.`id` = `posts`.`user_id`
                and exists (
                    select * from `comments`
                    where `posts`.`id` = `comments`.`post_id`
                    and exists (
                        select 1 from `users`
                        where
                            `comments`.`user_id` = `users`.`id`
                            and (`users`.`name` like ?)
                        )
                    )
                )
         ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                    {$innerSelect}
                )
                and `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            ['foo%', 'bar%'],
            $builder->getBindings()
        );
    }

    /** @test */
    public function it_can_search_multiple_relations()
    {
        $builder = User::search('foo bar baz', [
            'name',
            'email',
            'posts.title',
            'posts.excerpt',
            'posts.comments.body',
            'posts.comments.author.name',
        ]);

        $innerSelect = '
        select `matches`.`id` from (
            (
                select `users`.`id` as `id` from `users`
                where (`users`.`name` like ? or `users`.`email` like ?)
            )
            union
            (
                select `users`.`id` as `id` from `users`
                where exists (
                    select 1 from `posts`
                    where `users`.`id` = `posts`.`user_id`
                    and (`posts`.`title` like ? or `posts`.`excerpt` like ?)
                )
            )
            union
            (
                select `users`.`id` as `id` from `users`
                where exists (
                    select * from `posts`
                    where `users`.`id` = `posts`.`user_id`
                    and exists (
                        select 1 from `comments`
                        where `posts`.`id` = `comments`.`post_id`
                        and (`comments`.`body` like ?)
                    )
                )
            )
            union
            (
                select `users`.`id` as `id` from `users`
                where exists (
                    select * from `posts`
                    where `users`.`id` = `posts`.`user_id`
                    and exists (
                        select * from `comments`
                        where `posts`.`id` = `comments`.`post_id`
                        and exists (
                            select 1 from `users`
                            where
                            `comments`.`user_id` = `users`.`id`
                            and (`users`.`name` like ?)
                        )
                    )
                )
            )
         ) as `matches`';

        $this->assertSame(
            $this->trimLines("
                select * from `users` where `users`.`id` in (
                   {$innerSelect}
                )
                and `users`.`id` in (
                    {$innerSelect}
                )
                and `users`.`id` in (
                    {$innerSelect}
                )
            "),
            $builder->toSql()
        );

        $this->assertSame(
            [
                'foo%', 'foo%', 'foo%', 'foo%', 'foo%', 'foo%',
                'bar%', 'bar%', 'bar%', 'bar%', 'bar%', 'bar%',
                'baz%', 'baz%', 'baz%', 'baz%', 'baz%', 'baz%',
            ],
            $builder->getBindings()
        );
    }

    protected function trimLines(string $sql)
    {
        return str_replace(
            ['( ', ' )'],
            ['(', ')'],
            collect(explode(PHP_EOL, $sql))
                ->map(fn ($line) => trim($line))
                ->filter()
                ->filter(fn ($line) => '-- ' !== mb_substr($line, 0, 3))
                ->join(' ')
        );
    }
}

class User extends Model
{
    use Searchable;

    protected array $searchable = [
        'name',
        'email',
    ];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    use Searchable;

    protected array $searchable = [
        'title',
        'excerpt',
        'body',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model
{
    use Searchable;

    protected array $searchable = [
        'body',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
