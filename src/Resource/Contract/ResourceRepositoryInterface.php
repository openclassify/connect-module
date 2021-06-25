<?php namespace Visiosoft\ConnectModule\Resource\Contract;

use Visiosoft\ConnectModule\Resource\ResourceBuilder;
use Illuminate\Support\Collection;

/**
 * Interface ResourceRepositoryInterface
 *

 * @package       Visiosoft\ConnectModule\Resource\Contract
 */
interface ResourceRepositoryInterface
{

    /**
     * Get the resource entries.
     *
     * @param ResourceBuilder $builder
     * @return Collection
     */
    public function get(ResourceBuilder $builder);
}
