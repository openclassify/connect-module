<?php namespace Visiosoft\ConnectModule\Resource\Repository;

use Visiosoft\ConnectModule\Resource\Contract\ResourceRepositoryInterface;
use Visiosoft\ConnectModule\Resource\Event\ResourceIsQuerying;
use Visiosoft\ConnectModule\Resource\ResourceBuilder;
use Anomaly\Streams\Platform\Model\EloquentCollection;
use Anomaly\Streams\Platform\Model\EloquentModel;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class EloquentResourceRepository
 *

 * @package       Visiosoft\ConnectModule\Resource\Repository
 */
class EloquentResourceRepository implements ResourceRepositoryInterface
{

    use DispatchesJobs;

    /**
     * The repository model.
     *
     * @var EloquentModel
     */
    protected $model;

    /**
     * Create a new EloquentModel instance.
     *
     * @param EloquentModel $model
     */
    public function __construct(EloquentModel $model)
    {
        $this->model = $model;
    }

    /**
     * Get the resource entries.
     *
     * @param ResourceBuilder $builder
     * @return EloquentCollection
     */
    public function get(ResourceBuilder $builder)
    {
        /**
         * Start a new query.
         *
         * @var \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
         */
        $query = $this->model->newQuery();

        /**
         * Prevent joins from overriding intended formatters
         * by prefixing with the model's table name.
         */
        $query->select($this->model->getTable() . '.*');

        /**
         * Eager load any relations to
         * save resources and queries.
         */
        $query->with($builder->getResourceOption('eager', []));

        /**
         * Raise and fire an event here to allow
         * other things (including filters / views)
         * to modify the query before proceeding.
         */
        $builder->fire('querying', compact('builder', 'query'));
        app('events')->dispatch(new ResourceIsQuerying($builder, $query));

        /**
         * If a specific ID is desired than return
         * it now since we've already filtered.
         */
        if ($id = $builder->getId()) {
            return $query->where($this->model->getTable() . '.id', $id)->get();
        }

        /**
         * Before we actually adjust the baseline query
         * set the total amount of entries possible back
         * on the table so it can be used later.
         */
        $total = $query->count();

        $builder->setResourceOption('total_results', $total);

        /**
         * Assure that our page exists. If the page does
         * not exist then start walking backwards until
         * we find a page that is has something to show us.
         */
        $limit  = $builder->getResourceOption('limit', 100);
        $page   = app('request')->get('page', 1);
        $offset = $limit * ($page - 1);

        if ($total < $offset && $page > 1) {
            $url = str_replace('page=' . $page, 'page=' . ($page - 1), app('request')->fullUrl());

            header('Location: ' . $url);
        }

        /**
         * Limit the results to the limit and offset
         * based on the page if any.
         */
        $offset = $limit * (app('request')->get('page', 1) - 1);

        $query->take($limit)->offset($offset);

        /**
         * Order the query results.
         */
        foreach ($builder->getResourceOption('order_by') as $formatter => $direction) {
            $query->orderBy($formatter, $direction);
        }

        return $query->get();
    }
}
