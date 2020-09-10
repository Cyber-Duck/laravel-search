<?php

namespace CyberDuck\Search;

use Illuminate\Database\Eloquent\Collection;

class AllBuilder extends Builder
{
    /**
     * @return Collection
     */
    public function get()
    {
        return app(EngineManager::class)->engine()->getAll($this);
    }
}
