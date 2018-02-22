<?php

namespace Tokenly\TokenmapClient\Console;


use App\ConsulClient;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PopulateQuotesCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tokenmap:populate-quotes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tokenmap Quote Populator';



    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->comment('begin');
        $tokenmap_client = app('Tokenly\TokenmapClient\Client');
        $quote = $tokenmap_client->loadQuote();
        $this->comment('done');
    }



}
