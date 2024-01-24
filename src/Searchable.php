<?php

namespace Ditscheri\EloquentSearch;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    /**
     * Models using this trait should provide a $searchable property.
     *
     * protected array $searchable = [
     *    'title',
     *    'description',
     *    'author.name',
     *    'author.profiles.description',
     * ];
     */
    public function scopeSearch(Builder $query, string $term = null, ?array $columns = null): Builder
    {
        return (new EloquentSearchBuilder($query, $columns ?? $this->searchable, $this->searchableMode ?? 'right'))
            ->apply($term);
    }
}
