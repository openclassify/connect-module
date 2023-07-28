<?php namespace Visiosoft\ConnectModule\Resource\Command;

use Visiosoft\ConnectModule\Resource\ResourceBuilder;
use Illuminate\Contracts\Routing\ResponseFactory;

/**
 * Class MakeJsonResponse
 *
 * @package       Visiosoft\ConnectModule\Resource\Command
 */
class MakeJsonResponse
{

    /**
     * The resource builder.
     *
     * @var ResourceBuilder
     */
    protected $builder;

    /**
     * Create a new BuildResourceFormattersCommand instance.
     *
     * @param ResourceBuilder $builder
     */
    public function __construct(ResourceBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Handle the command.
     *
     * @param ResponseFactory $response
     */
    public function handle(ResponseFactory $response)
    {
        $data = $this->builder->getResourceEntries();

        if (is_array($data) && !count($data)) {
            return $response->json(['data' => $data]);
        }
        return $response->json($this->builder->getResourceData());
    }
}
