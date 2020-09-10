<?php

namespace CyberDuck\Search\Console;

use Illuminate\Console\Command;

class DeleteCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:delete {model?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Delete one or all indices";

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {

        $class = $this->argument('model');

        $classes = $class ? [$class]: config('laravel-search.models_all');

        foreach ($classes as $class) {
            $model = new $class;

            $model::deleteIndex();

            $this->info('Index for [' . $class . '] records have been deleted.');
        }
    }
}
