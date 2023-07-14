<?php namespace Anomaly\Streams\Platform\Ui\Entity\Command;

use Anomaly\Streams\Platform\Ui\Entity\Component\Action\Command\BuildActions;
use Anomaly\Streams\Platform\Ui\Entity\Component\Action\Command\SetActiveAction;
use Anomaly\Streams\Platform\Ui\Entity\Component\Button\Command\BuildButtons;
use Anomaly\Streams\Platform\Ui\Entity\Component\Field\Command\BuildFields;
use Anomaly\Streams\Platform\Ui\Entity\Component\Section\Command\BuildSections;
use Anomaly\Streams\Platform\Ui\Entity\EntityBuilder;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class BuildEntity
 *

 * @package       Anomaly\Streams\Platform\Ui\Entity\Command
 */
class BuildEntity
{

    use DispatchesJobs;

    /**
     * The entity builder.
     *
     * @var EntityBuilder
     */
    protected $builder;

    /**
     * Create a new BuildEntityColumnsCommand instance.
     *
     * @param EntityBuilder $builder
     */
    public function __construct(EntityBuilder $builder)
    {
        $this->builder = $builder;
    }

    /**
     * Handle the command.
     */
    public function handle()
    {
        /**
         * Setup some objects and options using
         * provided input or sensible defaults.
         */
        $this->dispatchSync(new SetEntityModel($this->builder));
        $this->dispatchSync(new SetEntityStream($this->builder));
        $this->dispatchSync(new SetDefaultParameters($this->builder));
        $this->dispatchSync(new SetRepository($this->builder));

        $this->dispatchSync(new SetEntityOptions($this->builder));
        $this->dispatchSync(new SetEntityEntry($this->builder)); // Do this last.

        /**
         * Before we go any further, authorize the request.
         */
        $this->dispatchSync(new AuthorizeEntity($this->builder));

        /*
         * Build entity fields.
         */
        $this->dispatchSync(new BuildFields($this->builder));

        /**
         * Build entity sections.
         */
        $this->dispatchSync(new BuildSections($this->builder));

        /**
         * Build entity actions and flag active.
         */
        $this->dispatchSync(new BuildActions($this->builder));
        $this->dispatchSync(new SetActiveAction($this->builder));

        /**
         * Build entity buttons.
         */
        $this->dispatchSync(new BuildButtons($this->builder));
    }
}
