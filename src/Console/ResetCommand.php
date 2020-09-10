<?php

namespace CyberDuck\Search\Console;

use Illuminate\Console\Command;

class ResetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'search:reset {model?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Reset one or all indices";

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
            $this->call("search:flush", ['model' => $class]);
            $this->call('search:delete', ['model' => $class]);
            $this->call('search:import', ['model' => $class]);

            $this->info('Index for [' . $class . '] has been reset.');
        }
    }
}
