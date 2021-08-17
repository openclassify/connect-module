<?php namespace Visiosoft\ConnectModule\Resource\Repository;

use Anomaly\Streams\Platform\Entry\EntryQueryBuilder;
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


        return $this->returnQuerying($query, $builder);
    }

    public function getRepositoryEntries(ResourceBuilder $builder)
    {
        $model = $builder->getModel();

        $search_parameters = $builder->getResourceOption("search_parameters", []);

        $search_function = $builder->getResourceOption("search_function", null);


        $query = $this->getRepositoryFunctions($model, $search_function, $search_parameters);

        if ($query instanceof EntryQueryBuilder) {
            return $this->returnQuerying($query, $builder);
        }

        return $query;
    }

    public function getModelEntries(ResourceBuilder $builder)
    {
        $model = $builder->getModel();

        $search_parameters = $builder->getResourceOption("search_parameters", []);

        $search_function = $builder->getResourceOption("search_function", null);


        $query = $this->getModelFunctions($model, $search_function, $search_parameters);

        if ($query instanceof EntryQueryBuilder) {
            return $this->returnQuerying($query, $builder);
        }

        return $query;
    }

    public function getModelFunctions($model, $function_name, array $params = [])
    {
        try {
            return call_user_func_array([app($model), camel_case($function_name)], $params);
        } catch (\Exception $exception) {
            echo json_encode(['message' => $exception->getMessage()]);
            die;
        }
    }

    public function getRepositoryFunctions($model, $function_name, array $params = [])
    {
        try {
            if ($model = call_user_func_array([app($model), camel_case('getRepository')], [])) {
                return call_user_func_array([app($model), $function_name], $params);
            }
        } catch (\Exception $exception) {
            echo json_encode(['message' => $exception->getMessage()]);
            die;
        }
    }

    public function returnQuerying($query, $builder)
    {
        /**
         * It allows you to add your Query Conditions.
         */

        $query_conditions = $builder->getResourceOption('where', "{}");


        if ($query_conditions = json_decode($query_conditions, true) and is_array($query_conditions)) {
            foreach ($query_conditions as $item_where) {
                if (isset($item_where['column']) && isset($item_where['value'])) {

                    $operator = (isset($item_where['operator'])) ? $item_where['operator'] : "=";

                    $query->where($item_where['column'], $operator, $item_where['value']);

                }
            }
        }

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
        $limit = $builder->getResourceOption('limit', 100);
        $page = app('request')->get('page', 1);
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
