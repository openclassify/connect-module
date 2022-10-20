<?php namespace Visiosoft\ConnectModule\Http\Controller\Resource;

use Visiosoft\ConnectModule\Command\GetRepository;
use Visiosoft\ConnectModule\Resource\ResourceBuilder;
use Anomaly\Streams\Platform\Http\Controller\ResourceController;
use Anomaly\Streams\Platform\Stream\Contract\StreamRepositoryInterface;

class FunctionController extends ResourceController
{
    public function index(ResourceBuilder $resources)
    {
        return $resources
            ->setFunction($this->route->parameter('function'))
            ->response(
                $this->route->parameter('namespace'),
                $this->route->parameter('stream')
            );
    }

    public function show(ResourceBuilder $resources)
    {
        return $resources
            ->setFunction($this->route->parameter('function'))
            ->setId($this->route->parameter('id'))
            ->response(
                $this->route->parameter('namespace'),
                $this->route->parameter('stream')
            );
    }

    public function store(ResourceBuilder $resources)
    {
        return $resources
            ->setFunction($this->route->parameter('function'))
            ->response(
                $this->route->parameter('namespace'),
                $this->route->parameter('stream')
            );
    }


    public function getRequestOptions()
    {
        return $this->request->get('options', []);
    }

    public function getOption($key, $default = null)
    {
        if (array_key_exists($key, $this->getRequestOptions())) {
            return $this->getRequestOptions()[$key];
        }

        return value($default);
    }
}
