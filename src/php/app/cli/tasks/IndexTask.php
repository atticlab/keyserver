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
        if (!$this->riak->fetchSchema(Wallets::RIAK_BUCKET, APP_PATH . '/riak_schemes/wallets.xml')) {
            if (!$this->riak_cli->createSchema(Wallets::RIAK_BUCKET, APP_PATH . '/riak_schemes/wallets.xml')) {
                throw new \Exception('Can not create search schema');
            } else {
                echo "\nSchema created\n";
                echo "Wait 5 sec for solr schema abracadabra...\n";
                sleep(5);
            }
        }

        $ignore_files = [
            'ModelBase.php'
        ];
        $files = array_diff(scandir(MODEL_PATH), array_merge(['.', '..'], $ignore_files));

        //create buckets with props
        foreach ($files as $file) {
            $model_name = str_replace('.php', '', $file);
            $model = '\App\Models\\' . $model_name;
            if ($model::RIAK_BUCKET) {
                if (!$this->riak_cli->setBucketProperty($model::RIAK_BUCKET, 'n_val', intval(getenv('N_VAL')))) {
                    echo "Can not create bucket with property for " . $model::RIAK_BUCKET . PHP_EOL;
                } else {
                    echo "Created bucket with property for " . $model::RIAK_BUCKET . PHP_EOL;
                }
            }
        }
        echo "Buckets created\n";
        echo "Wait 30 sec for solr buckets abracadabra...\n";
        sleep(30);

        //create indexes
        foreach ($files as $file) {
            $model_name = str_replace('.php', '', $file);
            $model = '\App\Models\\' . $model_name;
            if ($model::RIAK_BUCKET) {
                if (!$this->riak_cli->createIndex($model::RIAK_BUCKET, Wallets::RIAK_BUCKET, intval(getenv('N_VAL')))) {
                    echo "Can not create index for " . $model::RIAK_BUCKET . PHP_EOL;
                } else {
                    echo "Created index for " . $model::RIAK_BUCKET . PHP_EOL;
                }
            }
        }

        echo "Indexes created\n";
        echo "Wait 30 sec for solr indexes abracadabra...\n";
        sleep(30);

        //associate indexes to buckets
        foreach ($files as $file) {
            $model_name = str_replace('.php', '', $file);
            $model = '\App\Models\\' . $model_name;
            if ($model::RIAK_BUCKET) {
                if (!$this->riak_cli->associateIndex($model::RIAK_BUCKET, $model::RIAK_BUCKET)) {
                    echo "Can not associate index for " . $model::RIAK_BUCKET . PHP_EOL;
                } else {
                    echo "Associated index for " . $model::RIAK_BUCKET . PHP_EOL;
                }

            }
        }

        echo "Indexes associated\n";
        echo "Finished\n";
    }
}
