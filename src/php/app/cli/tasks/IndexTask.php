<?php
use App\Lib\LogsHandler;
use App\Models\Admins;
use App\Models\Wallets;
use Phalcon\CLI\Task;
use Phalcon\DI;

class IndexTask extends Task
{
    public function yokozunaAction()
    {
        if (!$this->riak->createSchema(Wallets::RIAK_BUCKET, APP_PATH . '/riak_schemes/wallets.xml')) {
            throw new \Exception('Can not create search schema');
        } else {
            echo "\nSchema created\n";
            echo "Wait 5 sec for solr schema abracadabra...\n";
            sleep(5);
        }

        //create indexes
        if (!$this->riak->createIndex(Wallets::RIAK_BUCKET, Wallets::RIAK_BUCKET)) {
            echo "Can not create index" . PHP_EOL;
        } else {
            echo "Created index" . PHP_EOL;
        }

        echo "Indexes created\n";
        echo "Wait 30 sec for solr indexes abracadabra...\n";
        sleep(30);

        if (!$this->riak->associateIndex(Wallets::RIAK_BUCKET, Wallets::RIAK_BUCKET)) {
            echo "Can not associate index for " . Wallets::RIAK_BUCKET . PHP_EOL;
        } else {
            echo "Associated index for " . Wallets::RIAK_BUCKET . PHP_EOL;
        }

        echo "Indexes associated\n";
        echo "Finished\n";
    }
}
