<?php

namespace App\Lib;

class Riak
{
    const MAX_FILE_BYTESIZE = 1024 * 1024 * 2;

    private $host;
    private $username;
    private $password;

    public function __construct($host, $username = null, $password = null)
    {
        if (!filter_var($host, FILTER_VALIDATE_URL)) {
            throw new \Exception('Riak error: invalid host provided');
        }

        $this->username = $username;
        $this->password = $password;
        $this->host = rtrim($host, '/');
    }

    public function search(Riak\Query $query)
    {
        $params = http_build_query([
            'wt'    => 'json',
            'q'     => $query->buildQuery(),
            'start' => $query->offset,
            'rows'  => $query->limit,
            'sort'  => '_yz_id desc', //default sort
        ]);

        $curl = $this->initCurl('/search/query/' . $query->bucket . '?' . $params);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curl);
        curl_close($curl);

        if (!empty($resp)) {
            return json_decode($resp, 1)['response'];
        }

        return $resp;
    }

    /**
     * Write to bucket
     * @param $bucket
     * @param $key
     * @param $data can be either string or an array
     * @return mixed
     */
    public function set($bucket, $key, $data)
    {
        $curl = $this->initCurl('/buckets/' . $bucket . '/keys/' . $key, $data);
        $success = curl_exec($curl);
        curl_close($curl);

        return $success;
    }

    /**
     * Get from bucket
     * @param $bucket
     * @param $key
     * @return mixed
     */
    public function get($bucket, $key)
    {
        $curl = $this->initCurl('/buckets/' . $bucket . '/keys/' . $key);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resp = curl_exec($curl);
        curl_close($curl);

        return json_decode($resp, 1);
    }

    /**
     * Delete from bucket
     * @param $bucket
     * @param $key
     * @return mixed
     */
    public function delete($bucket, $key)
    {
        $curl = $this->initCurl('/buckets/' . $bucket . '/keys/' . $key);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');

        $success = curl_exec($curl);
        curl_close($curl);

        return $success;
    }

    public function uploadBinary($bucket, $filepath, $key = null, $content_type = null)
    {
        $filepath = realpath($filepath);
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new \Exception('Riak error: cannot read file');
        }

        if (filesize($filepath) > self::MAX_FILE_BYTESIZE) {
            throw new \Exception('Riak error: file size exceeds allowed maximum: ' . self::MAX_FILE_BYTESIZE);
        }

        $key = $key ?? pathinfo($filepath, PATHINFO_BASENAME);
        if (empty($key)) {
            throw new \Exception('Empty key');
        }

        $curl = $this->initCurl('/buckets/' . $bucket . '/keys/' . $key);

        // Potentially this should work, but after php5.6 they add multiparts which break everthing
        // TODO: find a workaround for this
        //curl_setopt($curl, CURLOPT_POSTFIELDS, [
        //  'file' => new \CurlFile($filepath)
        //]);

        curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($filepath));

        $success = curl_exec($curl);
        curl_close($curl);

        return $success;
    }

    /**
     * Get binary from riak and save to file
     * @param $bucket
     * @param $key
     * @param $filepath
     * @return string
     */
    public function downloadBinary($bucket, $key, $filepath)
    {
        $dir = pathinfo($filepath, PATHINFO_DIRNAME);
        if (!is_writable($dir)) {
            throw new \Exception('Riak error: path is not writable ' . $dir);
        }

        $fp = fopen($filepath, 'w');
        $curl = $this->initCurl('/buckets/' . $bucket . '/keys/' . $key);
        curl_setopt($curl, CURLOPT_FILE, $fp);

        $success = curl_exec($curl);
        curl_close($curl);
        fclose($fp);

        return $success;
    }


    public function createSchema($schema_name, $filepath)
    {
        if (!file_exists($filepath) || !is_readable($filepath)) {
            throw new \Exception('Riak error: cannot read file');
        }

        if (filesize($filepath) > self::MAX_FILE_BYTESIZE) {
            throw new \Exception('Riak error: file size exceeds allowed maximum: ' . self::MAX_FILE_BYTESIZE);
        }

        $key = pathinfo($filepath, PATHINFO_BASENAME);
        if (empty($key)) {
            throw new \Exception('Cannot retreive filename');
        }

        $curl = $this->initCurl('/search/schema/' . $schema_name);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-type: application/xml']);
        curl_setopt($curl, CURLOPT_POSTFIELDS, file_get_contents($filepath));

        $success = curl_exec($curl);
        curl_close($curl);

        return $success;
    }

    public function createIndex($index_name, $schema = '_yz_default')
    {
        $curl = $this->initCurl('/search/index/' . $index_name, [
            'schema' => $schema
        ]);

        $success = curl_exec($curl);
        curl_close($curl);

        return $success;
    }

    public function associateIndex($bucket, $index_name)
    {
        $curl = $this->initCurl('/buckets/' . $bucket . '/props', [
            'props' => [
                'search_index' => $index_name
            ]
        ]);

        $success = curl_exec($curl);
        curl_close($curl);

        return $success;
    }

    private function initCurl($route, $data = null)
    {
        $curl = curl_init();

        if (!empty($this->username) && !empty($this->password)) {
            curl_setopt($curl, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => 0,
            CURLOPT_FAILONERROR    => 1,
            CURLOPT_URL            => $this->host . $route
        ]);

        if (!empty($data)) {
            curl_setopt_array($curl, [
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_HTTPHEADER    => ['Content-type: application/json'],
                CURLOPT_POSTFIELDS    => json_encode($data)
            ]);
        }

        return $curl;
    }
}
