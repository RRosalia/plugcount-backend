<?php

/**
 * BaseRepositoryInterface
 *
 * Base repository interface that acts as a proxy for Eloquent query builder methods.
 *
 * All repository implementations use the magic __call method to delegate method calls
 * to the Eloquent query builder, allowing you to use all Eloquent methods directly
 * on the repository instance (e.g., $repository->where(), $repository->find(), etc.).
 *
 * The builder instance is automatically managed and reset after terminal operations.
 *
 * @package App\Infrastructure\Repositories\Contracts
 *
 * @template TModel of Model
 *
 * @mixin Builder<TModel>
 *
 * @method static where($column, $operator = null, $value = null, $boolean = 'and')
 * @method static whereIn($column, $values, $boolean = 'and', $not = false)
 * @method static whereNotIn($column, $values, $boolean = 'and')
 * @method static whereNull($columns, $boolean = 'and', $not = false)
 * @method static whereNotNull($columns, $boolean = 'and')
 * @method static whereBetween($column, iterable $values, $boolean = 'and', $not = false)
 * @method static orWhere($column, $operator = null, $value = null)
 * @method static orderBy($column, $direction = 'asc')
 * @method static latest($column = 'created_at')
 * @method static oldest($column = 'created_at')
 * @method static limit($value)
 * @method static take($value)
 * @method static skip($value)
 * @method static offset($value)
 * @method static with($relations)
 * @method static withCount($relations)
 * @method static whereKey($id)
 * @method static whereHas($relation, Closure $callback = null, $operator = '>=', $count = 1)
 * @method static whereDoesntHave($relation, Closure $callback = null)
 * @method static has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
 * @method static doesntHave($relation, $boolean = 'and', Closure $callback = null)
 * @method TModel|null find($id, $columns = ['*'])
 * @method TModel findOrFail($id, $columns = ['*'])
 * @method TModel|null first($columns = ['*'])
 * @method TModel firstOrFail($columns = ['*'])
 * @method \Illuminate\Database\Eloquent\Collection get($columns = ['*'])
 * @method LengthAwarePaginator paginate($perPage = 15, $columns = ['*'], $pageName = 'page', $page = null)
 * @method TModel create(array $attributes = [])
 * @method TModel updateOrCreate(array $attributes, array $values = [])
 * @method TModel firstOrCreate(array $attributes = [], array $values = [])
 * @method int count($columns = '*')
 * @method bool exists()
 * @method bool doesntExist()
 * @method mixed sum($column)
 * @method mixed avg($column)
 * @method mixed min($column)
 * @method mixed max($column)
 * @method Collection pluck($column, $key = null)
 */

declare(strict_types=1);

namespace App\Infrastructure\Repositories\Contracts;

use Closure;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface BaseRepositoryInterface
{
    /**
     * Create a new query builder instance.
     *
     * @return Builder<TModel>
     */
    public function newQuery(): Builder;

    /**
     * Include soft-deleted records in the query.
     */
    public function withTrashed(): static;

    /**
     * Eager load media relationship.
     */
    public function withMedia(?Closure $closure = null): static;

    /**
     * Add media count to the query.
     */
    public function withMediaCount(?Closure $closure = null): static;

    /**
     * Lock the selected rows for update.
     */
    public function lockForUpdate(): static;
}
