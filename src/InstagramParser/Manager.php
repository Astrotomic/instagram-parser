<?php

namespace Astrotomic\InstagramParser;

use Astrotomic\InstagramParser\Endpoints\UserRecentMedia;
use Astrotomic\InstagramParser\Endpoints\TagRecentMedia;

class Manager
{
    protected $config = [];
    protected $client = [];

    public function __construct()
    {
        $config = [
            'media_limit'       => 100,
            'cache_time'        => 3600,
            'allowed_usernames' => '*',
            'allowed_tags'      => '*',
            'storage_path'      => __DIR__.'/storage',
        ];
        $client = [
            'base_url'   => 'https://www.instagram.com/',
            'cookie_jar' => [],
            'headers'    => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.87 Safari/537.36',
                'Origin'     => 'https://www.instagram.com',
                'Referer'    => 'https://www.instagram.com',
                'Connection' => 'close',
            ],
        ];
        $this->setConfig($config);
        $this->setClient($client);
    }

    public function setConfig($value, $key = null)
    {
        if (is_null($key)) {
            $this->config = $value;
        }
        $this->config[$key] = $value;
    }

    public function getConfig($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->config;
        }

        if(array_key_exists($key, $this->config) && !is_null($this->config[$key])) {
            return $this->config[$key];
        }
        return $default;
    }

    public function setClient($value, $key = null)
    {
        if (is_null($key)) {
            $this->client = $value;
        }
        $this->client[$key] = $value;
    }

    public function getClient($key = null)
    {
        if (is_null($key)) {
            return $this->client;
        }

        return $this->client[$key];
    }

    public function getUserRecentMedia($userName)
    {
        return (new UserRecentMedia($this))->handle($userName);
    }

    public function getTagRecentMedia($tag)
    {
        return (new TagRecentMedia($this))->handle($tag);
    }
}
