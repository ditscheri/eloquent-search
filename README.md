**PACKAGE IN DEVELOPMENT, DO NOT USE YET**

# Eloquent Search

[![Latest Version on Packagist](https://img.shields.io/packagist/v/ditscheri/eloquent-search.svg?style=flat-square)](https://packagist.org/packages/ditscheri/eloquent-search)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/ditscheri/eloquent-search/run-tests?label=tests)](https://github.com/ditscheri/eloquent-search/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/ditscheri/eloquent-search/Check%20&%20fix%20styling?label=code%20style)](https://github.com/ditscheri/eloquent-search/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/ditscheri/eloquent-search.svg?style=flat-square)](https://packagist.org/packages/ditscheri/eloquent-search)

This package lets you perform fast and local searches on your Eloquent Models. You can search foreign columns of related models too.

The approach is based on a gist by Jonathan Reinink (https://gist.github.com/reinink/28bd174087e929ffa1f150e3fe8ffbfa).

## Installation

You can install the package via composer:

```bash
composer require ditscheri/eloquent-search
```

## Usage

```php
class Podcast extends Model
{
    use \Ditscheri\EloquentSearch\Searchable;

    /**
     * The attributes that are searchable.
     *
     * @var string[]
     */
    protected array $searchable = [
        'title', // make sure to add proper indexes to each of these columns
        'description',
        'author.first_name',
        'author.last_name',
        'comments.message',
        'series.title',
        'series.tags.name',
    ];

    public function author()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function series()
    {
        return $this->belongsTo(Series::class);
    }
}

class PodcastController 
{
    public function index(Request $request)
    {
        return Podcast::search($request->input('q', null))->paginate();
    }
}

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Daniel Bakan](https://github.com/dbakan)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
