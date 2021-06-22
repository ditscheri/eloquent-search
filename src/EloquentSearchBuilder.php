<?php

namespace Ditscheri\EloquentSearch;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Collection;

class EloquentSearchBuilder
{
    protected Builder $query;

    protected array $columns;

    public function __construct(Builder $query, array $columns)
    {
        $this->query = $query;

        $this->columns = $columns;
    }

    public function apply(string $term = null): Builder
    {
        if (empty($term)) {
            return $this->query;
        }

        if (empty($this->columns)) {
            return $this->query;
        }

        // rename to $groups
        $columnGroups = $this->extractColumnGroups($this->columns);

        // If we're only searching the local table, we can keep it simple:
        // if($columnGroups->count() === 1 && $columnGroups->keys()->first() === '') {
        //     // ..
        // }

        collect(str_getcsv($term, ' ', '"'))
            ->filter()
            ->map(fn (string $term): string => EloquentSearch::toLikeOperand($term))
            ->each(
                fn (string $term) => $this->query->whereIn(
                    $this->query->getModel()->getQualifiedKeyName(),
                    fn ($query) => $query->select('matches.id')
                        ->from($this->buildSubSearch($columnGroups, $term), 'matches')
                )
            );

        return $this->query;
    }

    protected function extractColumnGroups(array $searchable): Collection
    {
        return collect($searchable)
            ->map(function (string $key): array {
                $parts = explode('.', $key);
                $column = array_pop($parts);
                $relation = implode('.', $parts);

                return [
                    'relation' => $relation,
                    'qualifiedColumn' => $this->resolveRelatedModel($relation)->qualifyColumn($column),
                ];
            })
            ->groupBy('relation')
            ->map(fn (Collection $group, string $relation): array => [
                'relation' => $relation,
                'qualifiedColumns' => $group->pluck('qualifiedColumn'),
            ])
            ->values();
    }

    protected function buildSubSearch(Collection $columnGroups, string $term): QueryBuilder
    {
        $query = with(
            $columnGroups->first(),
            fn ($columnGroup) => $this->buildUnionSub($columnGroup['relation'], $columnGroup['qualifiedColumns'], $term),
        );

        $columnGroups->skip(1)->each(fn ($columnGroup) => $query->union(
            $this->buildUnionSub($columnGroup['relation'], $columnGroup['qualifiedColumns'], $term)
        ));

        return $query->getQuery();
    }

    protected function buildUnionSub(string $relation, Collection $qualifiedColumns, string $term): Builder
    {
        return $this->newBaseQuery()->when(
            empty($relation),
            fn (Builder $query) => $this->applyLocalSearch($query, $qualifiedColumns, $term),
            fn (Builder $query) => $this->applyForeignSearch($query, $relation, $qualifiedColumns, $term),
        );
    }

    protected function newBaseQuery(): Builder
    {
        return $this->query->getModel()->newQuery()
            ->withoutGlobalScopes()
            ->select($this->query->getModel()->getQualifiedKeyName().' AS id');
    }

    protected function applyLocalSearch(Builder $query, $qualifiedColumns, string $term): Builder
    {
        /** @psalm-suppress UnusedClosureParam */
        return $query->where(fn (Builder $innerQuery) => $qualifiedColumns->each(
            fn ($column) => $innerQuery->orWhere($column, 'like', $term)
        ));
    }

    protected function applyForeignSearch(Builder $query, string $relation, $qualifiedColumns, string $term): Builder
    {
        return $query->whereHas(
            $relation,
            fn (Builder $query) => $this->applyLocalSearch($query->select(new Expression('1')), $qualifiedColumns, $term)
        );
    }

    protected function resolveRelatedModel(string $relation): Model
    {
        return collect(explode('.', $relation))
            ->filter()
            ->reduce(
                fn (Model $carry, string $rel): Model => $carry->{$rel}()->getModel(),
                $this->query->getModel()
            );
    }
}
