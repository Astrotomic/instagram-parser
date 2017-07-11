<?php

namespace Astrotomic\InstagramParser\Traits;

trait Cacheable
{
    protected function getData($key, $validCache = true)
    {
        $cacheTime = $this->getCacheTime();
        $fileName = md5($key);
        $storagePath = $this->getStoragePath();
        $filePath = $storagePath.'/'.$fileName.'.csv';
        if (!is_readable($filePath)) {
            return null;
        }
        $resource = fopen($filePath, 'r');
        $csv = fgetcsv($resource, null, ';');
        if (!$csv || count($csv) != 3 || ($validCache && time() > $csv[1] + $cacheTime)) {
            return null;
        }

        return json_decode($csv[2], true);
    }

    protected function putData($key, $data)
    {
        $fileName = md5($key);
        $storagePath = $this->getStoragePath();
        $filePath = $storagePath.'/'.$fileName.'.csv';
        if (!is_dir($storagePath) && !@mkdir($storagePath, 0775, true)) {
            return false;
        }
        $resource = fopen($filePath, 'w');
        fputcsv($resource, [$key, time(), json_encode($data)], ';');
        fclose($resource);

        return true;
    }

    protected function getCacheTime()
    {
        $cacheTime = (int) $this->getConfig('cache_time');

        return ($cacheTime > 0) ? $cacheTime : 3600;
    }

    protected function getStoragePath()
    {
        return rtrim($this->getConfig('storage_path'), '/');
    }
}
