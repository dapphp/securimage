<?php

namespace Securimage\StorageAdapter;

use Securimage\StorageAdapter\AdapterInterface;

class Redis implements AdapterInterface
{
    protected $server;
    protected $persistent;
    protected $dbindex;
    protected $expiration;

    protected $redis;

    public function __construct($options = null)
    {
        if (!class_exists('Redis')) {
            throw new \Exception("Redis extension is not enabled.  Securimage Redis adapter cannot function");
        }

        if (!is_array($options)) {
            throw new \Exception("Options supplied to Securimage Redis adapter must be an array");
        }

        if (!isset($options['redis_server'])) {
            throw new \Exception("'redis_server' option was supplied to StorageAdapter\Redis::__construct");
        }

        if (!is_string($options['redis_server'])) {
            throw new \Exception("'redis_server' option must be a string");
        }

        $this->server     = $options['redis_server'];
        $this->persistent = @$options['redis_persistent'];
        $this->expiration = ((int)$options['expiration'] > 0) ? (int)$options['expiration'] : 900;
        $this->dbindex    = (isset($options['redis_dbindex'])) ? $options['redis_dbindex'] : null;

        $this->bootstrap();
    }

    public function store($captchaId, $captchaInfo)
    {
        $hash = array();
        foreach($captchaInfo as $prop => $val) {
            // convert CaptchaObject to array
            $hash[$prop] = $val;
        }

        if ($this->redis->hMSet($captchaId, $hash)) {
            $this->redis->setTimeout($captchaId, $this->expiration);
            return true;
        } else {
            return false;
        }
    }

    public function storeAudioData($captchaId, $audioData)
    {
        return $this->redis->hSet($captchaId, 'captchaAudioData', $audioData) !== false;
    }

    public function get($captchaId, $what = null)
    {
        $result = null;

        if (is_null($what)) {
            $result = $this->redis->hGetAll($captchaId);
        } else {
            if (is_string($what)) $what = array($what);
            if (!is_array($what)) {
                trigger_error(
                    "'what' parameter passed to StorageAdapter\Redis::get was neither an array nor string",
                    E_USER_WARNING
                );
                return false;
            }

            $result = $this->redis->hMget($captchaId, $what);
        }

        if ($result) {
            $info = new \Securimage\CaptchaObject;
            foreach($result as $key => $val) {
                $info->$key = $val;
            }

            return $info;
        }

        return null;
    }

    public function delete($captchaId)
    {
        return $this->redis->del($captchaId) > 0;
    }

    protected function bootstrap()
    {
        $this->redis = new \Redis();

        $connect = 'connect';

        if ($this->persistent) {
            $connect = 'pconnect';
        }

        $server = $this->server;
        $parts  = explode(':', $server, 2);
        $server = $parts[0];
        $port   = (isset($parts[1]) ? $parts[1] : null);

        if ($this->redis->$connect($server, $port)) {
            $this->redis->setOption(\Redis::OPT_PREFIX, 'securimage:');
            if ($this->dbindex) {
                $this->redis->select($this->dbindex);
            }
        }
    }

    protected function getKey($captchaId)
    {
        return 'securimage:' . $captchaId;
    }
}
