<?php

namespace CyberDuck\Search\Console;

use Illuminate\Console\Command;

class FlushCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:flush {model?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Flush all of the model's records from the index";

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

            $model::removeAllFromSearch();

            $this->info('All [' . $class . '] records have been flushed.');
        }
    }
}