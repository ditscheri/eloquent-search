<?php

namespace Ditscheri\EloquentSearch;

class EloquentSearch
{
    public static function toLikeOperand(string $term): string
    {
        return self::escapeLike($term).'%';
    }

    public static function escapeLike($str): string
    {
        // TODO: Needs Test
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\%', '\_'],
            $str
        );
    }
}
