<?php

namespace Securimage\StorageAdapter;

use Securimage\StorageAdapter\AdapterInterface;

class Memcached implements AdapterInterface
{
    protected $memcached_servers;
    protected $persistent;
    protected $expiration;

    protected $memcached;

    public function __construct($options = null)
    {
        if (!class_exists('Memcached')) {
            throw new \Exception("Memcached extension is not enabled.  Securimage Memcached adapter cannot function");
        }

        if (!is_array($options)) {
            throw new \Exception("Options supplied to Securimage Memcached adapter must be an array");
        }

        if (!isset($options['memcached_servers'])) {
            throw new \Exception("'memcached_servers' option was supplied to StorageAdapter\Memcached::__construct");
        }

        if (!is_array($options['memcached_servers'])) {
            throw new \Exception("'memcached_servers' option must be an array of servers");
        }

        $this->memcached_servers = $options['memcached_servers'];
        $this->persistent        = @$options['memcached_persistent'];
        $this->expiration        = ((int)$options['expiration'] > 0) ? (int)$options['expiration'] : 900;

        $this->bootstrap();
    }

    public function store($captchaId, $captchaInfo)
    {
        return $this->memcached->set($this->getKey($captchaId), $captchaInfo, $this->expiration);
    }

    public function storeAudioData($captchaId, $audioData)
    {
        $info = $this->get($captchaId);

        if ($info && $this->delete($captchaId)) {
            $info->captchaAudioData = $audioData;

            return $this->store($captchaId, $audioData);
        }

        return false;
    }

    public function get($captchaId, $what = null)
    {
        $result = $this->memcached->get($this->getKey($captchaId));

        if (!$result) {
            if ($this->memcached->getResultCode() != \Memcached::RES_NOTFOUND) {
                trigger_error(
                    sprintf("Securimage Memcached::get failed with error %d: %s",
                        $this->memcached->getResultCode(),
                        $this->memcached->getResultMessage()
                    ),
                    E_USER_WARNING
                );
            }
        } else {
            return $result;
        }

        return null;
    }

    public function delete($captchaId)
    {
        return $this->memcached->delete($this->getKey($captchaId));
    }

    protected function bootstrap()
    {
        $this->memcached = new \Memcached($this->persistent);

        foreach($this->memcached_servers as $server) {
            $parts = explode(':', $server);
            $host  = $parts[0];
            $port  = (isset($parts[1]) ? $parts[1] : 11211);

            $this->memcached->addServer($host, $port);
        }
    }

    protected function getKey($captchaId)
    {
        return 'securimage:' . $captchaId;
    }
}
