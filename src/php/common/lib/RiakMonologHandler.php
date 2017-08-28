<?php
namespace App\Lib;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use \App\Models\Logs;
/**
 * This class is a handler for Monolog, which can be used
 * to write records in a Riak bucket
 *
 * Class MySQLHandler
 * @package wazaari\MysqlHandler
 */
class RiakMonologHandler extends AbstractProcessingHandler
{
    /**
     * @var string the table to store the logs in
     */
    private $bucket = Logs::RIAK_BUCKET;

    /**
     * Constructor of this class, sets the PDO and calls parent constructor
     *
     * @param string $bucket               Bucket in riak to store the logs in
     * @param bool|int $level           Debug level which this handler should store
     */
    public function __construct(
        $bucket,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        $this->bucket = $bucket;
        parent::__construct($level, $bubble);
    }
    /**
     * Writes the record down to the log of the implementing handler
     *
     * @param  $record[]
     * @return void
     */
    protected function write(array $record)
    {
        $log = new Logs();
        $log->Level = Logs::getLevels()[$record['level']] ?? 'error';
        $log->Message = $record['formatted'];
        $log->Fields = $record;
        $log->Service = 'KeyServer';
        $log->IsProcessed = false;
        $log->Node = getenv('HOST');
        $log->_type = 'Log';
        $log->Timestamp = round(microtime(true) * 1000);

        try {
            $log->save();
        } catch (\Exception $e) {
            error_log('Can not save error to riak with RiakMonologHandler');
            error_log($e->getMessage());
        }
    }
}