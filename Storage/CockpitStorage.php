<?php

/**
* TODO: improve key unique identifier: anonymous browsing
* keeps hiting cache (use Hybridauth\Storage\Session instead)
*/

namespace SocialLogin\Storage;

use Hybridauth\Storage\StorageInterface;

class CockpitStorage implements StorageInterface
{
    /**
     * Cockpit app
     *
     * @var object
     */
    private $app = null;

    /**
     * Key unique identifier
     *
     * @var string
     */
    public $identifier = null;

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = 'hybridauth:';

    /**
     * TTL (seconds)
     *
     * @var integer
     */
    protected $ttl = 60 * 5;

    /**
    * Initiate a new session
    *
    * @throws RuntimeException
    */
    public function __construct($app = null)
    {
        if (!$app) {
            return;
        }
        $this->app = $app;
        $this->identifier = md5($_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']);
        $this->flush();
    }

    /**
    * {@inheritdoc}
    */
    public function flush()
    {
        $keys = $this->app->memory->keys($this->keyPrefix.'*');
        if (!empty($keys)) {
            foreach($keys as $key){
                $obj = $this->app->memory->get($key,false);
                if($obj && ($obj['ttl'] + $this->ttl) < time()) {
                    $this->app->memory->del($key);
                }
            }
        }
    }

    /**
    * {@inheritdoc}
    */
    public function get($key)
    {
        $key = $this->keyPrefix . $this->identifier . '.' . strtolower($key);
        $content = $this->app->memory->get($key,null);
        return $content['value'] ?? null;
    }

    /**
    * {@inheritdoc}
    */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . $this->identifier . '.' . strtolower($key);
        $content = [
            'ttl' => time(),
            'value' => $value
        ];
        $this->app->memory->set($key, $content);
    }

    /**
    * {@inheritdoc}
    */
    public function clear()
    {
        $keys = $this->app->memory->keys($this->keyPrefix.$this->identifier.'.*');
        if (!empty($keys)) {
            $this->memory->del(...$keys);
        }
    }

    /**
    * {@inheritdoc}
    */
    public function delete($key)
    {
        $key = $this->keyPrefix . $this->identifier . '.' . strtolower($key);
        $this->app->memory->del($key);
    }

    /**
    * {@inheritdoc}
    */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . $this->identifier . '.' . strtolower($key);
        $this->app->memory->del($key);
    }
}