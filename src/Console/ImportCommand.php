<?php

namespace CyberDuck\Search\Console;

use CyberDuck\Search\Events\ModelsImported;
use Illuminate\Console\Command;
use Illuminate\Contracts\Events\Dispatcher;

class ImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:import
            {model? : Class name of model to bulk import}
            {--c|chunk= : The number of records to import at a time (Defaults to 500)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import given model or all from config into the search index';

    /**
     * Execute the console command.
     *
     * @param Dispatcher $events
     * @return void
     */
    public function handle(Dispatcher $events)
    {
        $class = $this->argument('model');

        $classes = $class ? [$class]: config('laravel-search.models_all');

        foreach ($classes as $class){


            $model = new $class;

            $events->listen(ModelsImported::class, function ($event) use ($class) {
                $key = $event->models->last()->getSearchKey();

                $this->line('<comment>Imported ['.$class.'] models up to ID:</comment> '.$key);
            });

            $model::makeAllSearchable($this->option('chunk'));

            $events->forget(ModelsImported::class);

            $this->info('All ['.$class.'] records have been imported.');
        }
    }
}
