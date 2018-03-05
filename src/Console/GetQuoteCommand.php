<?php

namespace Tokenly\TokenmapClient\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputArgument;

class GetQuoteCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'tokenmap:get-quote';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Loads and caches a quote from Tokenmap';

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['currency', InputArgument::OPTIONAL, 'Quote currency', 'USD'],
            ['token', InputArgument::OPTIONAL, 'Quote token', 'BTC'],
            ['chain', InputArgument::OPTIONAL, 'Quote chain', 'bitcoin'],
        ];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $this->comment('begin');

        $tokenmap_client = app('Tokenly\TokenmapClient\Client');

        $currency = $this->input->getArgument('currency');
        $token = $this->input->getArgument('token');
        $chain = $this->input->getArgument('chain');

        $quote = $tokenmap_client->getQuote($currency, $token, $chain);
        $this->info(json_encode($quote, 192));

        $this->comment('done');
    }

}
