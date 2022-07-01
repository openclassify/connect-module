<?php namespace Visiosoft\ConnectModule\Http\Controller\Resource;

use Visiosoft\ConnectModule\Traits\ApiReturnResponseTrait;
use Visiosoft\ConnectModule\Command\GetRepository;
use Visiosoft\ConnectModule\Resource\ResourceBuilder;
use Anomaly\Streams\Platform\Http\Controller\ResourceController;
use Anomaly\Streams\Platform\Stream\Contract\StreamRepositoryInterface;

class FunctionController extends ResourceController
{
    use ApiReturnResponseTrait;
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

    public function store(StreamRepositoryInterface $streams)
    {
        $stream = $streams->findBySlugAndNamespace(
            $this->route->parameter('stream'),
            $this->route->parameter('namespace')
        );

        $repository = $this->dispatch(new GetRepository($stream->getEntryModelName()));

        $function = $this->route->parameter('function');

        $parameters = $this->getOption('parameters', []);

        try {

            $entry = call_user_func([$repository, camel_case($function)], $parameters);

        } catch (\Exception $exception) {
            $error_code = $exception->getCode();

            $error_list = trans("visiosoft.module.connect::errors");

            $message = (!in_array($error_code, array_keys($error_list))) ? $exception->getMessage() : trans("visiosoft.module.connect::errors." . $error_code);
            return $this->sendError($message, "", [], 200);
        }
        if (!$entry) {
            return $this->sendError('', "", [], 200);

        }
        return $this->sendResponse($entry, '');
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
