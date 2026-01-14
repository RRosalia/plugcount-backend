<?php

/**
 * BaseRepository
 *
 * Base repository class that acts as a proxy for Eloquent query builder methods.
 *
 * This class uses the magic __call method to delegate all method calls to the Eloquent query builder,
 * allowing you to use all Eloquent methods directly on the repository instance (e.g., $this->where(),
 * $this->create(), $this->find(), etc.).
 *
 * The builder instance is automatically managed and reset after terminal operations (operations that
 * return a result rather than a builder instance).
 *
 * @package App\Infrastructure\Repositories\Eloquent
 *
 * @template TModel of Model
 *
 * @mixin Builder<TModel>
 */

namespace App\Infrastructure\Repositories\Eloquent;

use Closure;
use Exception;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

abstract class BaseRepository
{
    /**
     * @var Builder<TModel>|null
     */
    private ?Builder $builder = null;

    /**
     * Flag to allow mass operations (update/delete without constraints).
     * Must be set explicitly via allowMassOperation() before each mass operation.
     */
    private bool $allowMassOperation = false;

    /**
     * Get the model class name.
     *
     * @return class-string<TModel> The model class name.
     */
    abstract public function model(): string;

    /**
     * Include soft-deleted records in the query.
     *
     * @throws Exception
     */
    public function withTrashed(): static
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($this->model()))) {
            throw new Exception($this->model() . ' does not use SoftDeletes');
        }

        return $this->__call(__FUNCTION__, []);
    }

    /**
     * Magic method to handle dynamic method calls to the builder.
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        $this->ensureWeHaveBuilder();

        try {
            $result = $this->builder->$method(...$parameters);
        } catch (Exception $e) {
            $this->builder = null;
            throw $e;
        }

        if ($result instanceof Builder) {
            $this->builder = $result;

            return $this;
        }

        $this->resetCriteria();

        return $result;
    }

    /**
     * Ensure we have a builder instance.
     */
    private function ensureWeHaveBuilder(): void
    {
        if (is_null($this->builder)) {
            $this->builder = $this->newQuery();
        }
    }

    /**
     * Create a new query builder instance.
     *
     * @return Builder<TModel>
     */
    public function newQuery(): Builder
    {
        /** @var class-string<TModel> $modelClass */
        $modelClass = $this->model();

        return $modelClass::query();
    }

    /**
     * Reset the query builder and any applied criteria.
     *
     * This clears the current builder state, allowing fresh queries
     * to be built without any previously applied criteria or conditions.
     */
    public function resetCriteria(): static
    {
        $this->builder = null;

        return $this;
    }

    /**
     * Eager load media relationship.
     */
    public function withMedia(?Closure $closure = null): static
    {
        $this->with(['media' => $closure ?? function () {}]);

        return $this;
    }

    /**
     * Add media count to the query.
     */
    public function withMediaCount(?Closure $closure = null): static
    {
        $this->withCount(['media' => $closure ?? function () {}]);

        return $this;
    }

    /**
     * Eager load asset media relationship.
     */
    public function withAssetMedia(?Closure $closure = null): static
    {
        $this->with(['asset.media' => $closure ?? function () {}]);

        return $this;
    }

    /**
     * Lock the selected rows for update.
     */
    public function lockForUpdate(): static
    {
        $this->ensureWeHaveBuilder();

        $this->builder->lockForUpdate();

        return $this;
    }

    /**
     * Apply a criteria to the query builder.
     *
     * @param object $criteria A criteria object with an apply() method
     */
    public function pushCriteria(object $criteria): static
    {
        $this->ensureWeHaveBuilder();

        $result = $criteria->apply($this->builder, $this);

        if ($result instanceof Builder) {
            $this->builder = $result;
        }

        return $this;
    }

    /**
     * Apply a scope/callback to the query builder.
     *
     * @param Closure $callback The callback that receives and modifies the builder
     */
    public function scopeQuery(Closure $callback): static
    {
        $this->ensureWeHaveBuilder();

        $result = $callback($this->builder);

        if ($result instanceof Builder) {
            $this->builder = $result;
        }

        return $this;
    }

    /**
     * Allow the next update or delete operation to run without constraints.
     *
     * Use this when you intentionally want to update/delete all records.
     * The flag is automatically reset after the operation completes.
     */
    public function allowMassOperation(): static
    {
        $this->allowMassOperation = true;

        return $this;
    }

    /**
     * Update records in the database.
     *
     * This method includes safety checks to prevent accidental mass updates.
     *
     * @param array $values The values to update
     * @return int Number of affected rows
     *
     * @throws RuntimeException
     */
    public function update(array $values): int
    {
        if (func_num_args() > 1) {
            $secondArg = func_get_arg(1);
            throw new RuntimeException(
                'BaseRepository::update() called with 2 parameters. This would cause a mass update bug. ' .
                "The second parameter '{$secondArg}' is ignored by Eloquent Builder. " .
                'Use $repository->whereKey($id)->update($data) instead of $repository->update($data, $id).'
            );
        }

        if (! $this->allowMassOperation && ! $this->hasConstraints()) {
            throw new RuntimeException(
                'BaseRepository::update() called without any WHERE constraints. ' .
                'This would update ALL records in the table. ' .
                'Use ->whereKey($id)->update($data) to update a specific record, ' .
                'or ->allowMassOperation()->update($data) if mass update is intentional.'
            );
        }

        $this->allowMassOperation = false;

        return $this->__call('update', [$values]);
    }

    /**
     * Check if the current builder has any WHERE constraints applied.
     */
    private function hasConstraints(): bool
    {
        $this->ensureWeHaveBuilder();

        $wheres = $this->builder->getQuery()->wheres ?? [];

        return count($wheres) > 0;
    }

    /**
     * Delete records from the database.
     *
     * This method includes safety checks to prevent accidental mass deletes.
     *
     * @return int Number of affected rows
     *
     * @throws RuntimeException|Exception
     */
    public function delete(): int
    {
        if (! $this->allowMassOperation && ! $this->hasConstraints()) {
            throw new RuntimeException(
                'BaseRepository::delete() called without any WHERE constraints. ' .
                'This would delete ALL records in the table. ' .
                'Use ->whereKey($id)->delete() to delete a specific record, ' .
                'or ->allowMassOperation()->delete() if mass delete is intentional.'
            );
        }

        $this->allowMassOperation = false;

        return $this->__call('delete', []);
    }

    /**
     * Get the current Eloquent Builder instance.
     *
     * @return Builder<TModel>
     */
    protected function getEloquentBuilder(): Builder
    {
        $this->ensureWeHaveBuilder();

        return $this->builder;
    }
}
