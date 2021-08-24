<?php namespace Visiosoft\ConnectModule\Command;

class GetRepository
{
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        return $this->getRepositoryWithModel($this->model);
    }

    public function getRepositoryWithModel($model)
    {
        $model = get_class(app($model));
        $modelNamespace = explode('\\', $model);
        $modelName = array_pop($modelNamespace);
        preg_match('/^(.*)Model$/', $modelName, $m);
        $modelName = $m[1];
        $repoNamespace = implode('\\', $modelNamespace);

        return app("$repoNamespace\Contract\\{$modelName}RepositoryInterface");
    }
}
