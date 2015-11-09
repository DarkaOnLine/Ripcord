<?php

namespace Ripcord\Providers\Laravel\Console;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ripcord:publish';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish config ripcord config';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->info('Publishing config');
        $this->call('vendor:publish', [
            '--provider' => 'Ripcord\Providers\Laravel\ServiceProvider',
        ]);
    }
}
