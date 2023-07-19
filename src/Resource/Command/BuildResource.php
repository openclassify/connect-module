<?php namespace Visiosoft\ConnectModule\Resource\Command;

use Visiosoft\ConnectModule\Resource\Component\Result\Command\BuildResults;
use Visiosoft\ConnectModule\Resource\ResourceBuilder;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class BuildResource
 *

 * @package       Visiosoft\ConnectModule\Resource\Command
 */
class BuildResource
{

    use DispatchesJobs;

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
     */
    public function handle()
    {
        /**
         * Resolve and set the resource model and stream.
         */
        $this->dispatchSync(new HydrateFromRequest($this->builder));
        $this->dispatchSync(new SetResourceModel($this->builder));
        $this->dispatchSync(new SetResourceStream($this->builder));
        $this->dispatchSync(new SetDefaultParameters($this->builder));
        $this->dispatchSync(new SetRepository($this->builder));

        $this->dispatchSync(new SetResourceOptions($this->builder));
        $this->dispatchSync(new SetDefaultOptions($this->builder));

        /**
         * Before we go any further, authorize the request.
         */
        $this->dispatchSync(new AuthorizeResource($this->builder));

        /**
         * Get resource entries.
         */
        $this->dispatchSync(new GetResourceEntries($this->builder));

        /**
         * Lastly resource results.
         */
        $this->dispatchSync(new BuildResults($this->builder));

    }
}
