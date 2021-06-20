<?php

namespace Ditscheri\EloquentSearch\Commands;

use Illuminate\Console\Command;

class EloquentSearchCommand extends Command
{
    public $signature = 'eloquent-search';

    public $description = 'My command';

    public function handle()
    {
        $this->comment('All done');
    }
}
