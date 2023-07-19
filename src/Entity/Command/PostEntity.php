<?php namespace Anomaly\Streams\Platform\Ui\Entity\Command;

use Anomaly\Streams\Platform\Ui\Entity\EntityBuilder;
use Illuminate\Foundation\Bus\DispatchesJobs;

/**
 * Class PostEntity
 *

 * @package       Anomaly\Streams\Platform\Ui\Entity\Command
 */
class PostEntity
{

    use DispatchesJobs;

    /**
     * The entity builder.
     *
     * @var EntityBuilder
     */
    protected $builder;

    /**
     * Create a new PostEntity instance.
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
        $this->builder->fire('posting', ['builder' => $this->builder]);
        $this->builder->fireFieldEvents('entity_posting');

        $this->dispatchSync(new LoadEntityValues($this->builder));
        $this->dispatchSync(new ValidateEntity($this->builder));
        $this->dispatchSync(new RemoveSkippedFields($this->builder));
        $this->dispatchSync(new HandleEntity($this->builder));
        $this->dispatchSync(new SetSuccessMessage($this->builder));
        $this->dispatchSync(new SetActionResponse($this->builder));

        if ($this->builder->isAjax()) {
            $this->dispatchSync(new SetJsonResponse($this->builder));
        }

        $this->builder->fire('posted', ['builder' => $this->builder]);
        $this->builder->fireFieldEvents('entity_posted');
    }
}
