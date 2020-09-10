<?php


namespace CyberDuck\Search;

use CyberDuck\Search\Events\ModelsFlushed;
use CyberDuck\Search\Events\ModelsImported;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SearchableScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param EloquentBuilder $builder
     * @param Model $model
     * @return void
     */
    public function apply(EloquentBuilder $builder, Model $model)
    {
        //
    }

    /**
     * Extend the query builder with the needed functions.
     *
     * @param EloquentBuilder $builder
     * @return void
     */
    public function extend(EloquentBuilder $builder)
    {
        $builder->macro('searchable', function (EloquentBuilder $builder, $chunk = null) {
            $builder->chunkById($chunk ?: 500, function ($models) {
                $models->filter->shouldBeSearchable()->searchable();

                event(new ModelsImported($models));
            });
        });

        $builder->macro('unsearchable', function (EloquentBuilder $builder, $chunk = null) {
            $builder->chunkById($chunk ?: 500, function ($models) {
                $models->unsearchable();

                event(new ModelsFlushed($models));
            });
        });
    }
}