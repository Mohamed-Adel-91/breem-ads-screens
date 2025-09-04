<?php

use App\Enums\FilterType;
use Illuminate\Database\Eloquent\Builder;

if (!function_exists('applyFilter')) {
    /**
     * Apply a filter based on its type
     *
     * @param Builder $query
     * @param string $column
     * @param mixed $value
     * @param FilterType $type
     * @param array $options Additional options (like relation name for related filters)
     * @return Builder
     */
    function applyFilter(Builder $query, string $column, mixed $value, FilterType $type, ?string $relation = ''): Builder
    {
        if (is_null($value) || $value === '' || $value === [] || empty($value)) {
            return $query;
        }
        return match ($type) {
            FilterType::EXACT => exactFilter($query, $column, $value),
            FilterType::DATE_FROM => dateRangeFilter($query, $column, $value, '>='),
            FilterType::DATE_TO => dateRangeFilter($query, $column, $value, '<='),
            FilterType::RELATED => relatedFilter($query, $relation, $column, $value),
            FilterType::RANGE => rangeFilter($query, $column, $value),
            FilterType::LIKE => searchFilter($query, $column, $value),
        };
    }
}

if (!function_exists('exactFilter')) {
    /**
     * Apply exact match filter.
     *
     * @param Builder $query
     * @param string $column
     * @param mixed $value
     * @return Builder
     */
    function exactFilter(Builder $query, string $column, mixed $value): Builder
    {
        if (!is_null($value)) {
            $query->where($column, $value);
        }
        return $query;
    }
}

if (!function_exists('dateRangeFilter')) {
    /**
     * Apply date range filter.
     *
     * @param Builder $query
     * @param string $column
     * @param array $range
     * @return Builder
     */
    function dateRangeFilter(Builder $query, string $column, string $date, string $operator): Builder
    {
        if (!empty($date)) {
            $query->whereDate($column, $operator, $date);
        }
        return $query;
    }
}

if (!function_exists('relatedFilter')) {
    /**
     * Apply related model filter.
     *
     * @param Builder $query
     * @param string $relation
     * @param string $column
     * @param mixed $value
     * @return Builder
     */
    function relatedFilter(Builder $query, ?string $relation, string $column, mixed $value): Builder
    {
        if (!is_null($value) && !empty($relation)) {
            $query->whereRelation($relation, $column, $value);
        }
        return $query;
    }
}

if (!function_exists('rangeFilter')) {
    /**
     * Apply range filter that expects comma-separated values.
     *
     * @param Builder $query
     * @param string $column
     * @param string $rangeString
     * @return Builder
     */
    function rangeFilter(Builder $query, string $column, string $rangeString): Builder
    {
        if (!empty($rangeString)) {
            $parts = explode(',', $rangeString);
            if (count($parts) === 2) {
                [$from, $to] = $parts;
                $query->whereBetween($column, [$from, $to]);
            }
        }
        return $query;
    }
}

if (!function_exists('searchFilter')) {
    /**
     * Apply a partial match (LIKE) filter.
     *
     * @param Builder $query
     * @param string $column
     * @param string $searchTerm
     * @return Builder
     */
    function searchFilter(Builder $query, string $column, string $searchTerm): Builder
    {
        if (!empty($searchTerm)) {
            $searchTerm = trim($searchTerm);
            $query->where($column, 'LIKE', "%{$searchTerm}%");
        }
        return $query;
    }
}
