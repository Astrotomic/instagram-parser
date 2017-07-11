<?php

namespace Astrotomic\InstagramParser\Tests;

use Astrotomic\InstagramParser\InstagramParser;
use Astrotomic\InstagramParser\Manager;
use PHPUnit_Framework_TestCase;

class InstagramTestCase extends PHPUnit_Framework_TestCase
{
    protected function getInstagramParser()
    {
        $instagram = new Manager();
        $instagram->setConfig(__DIR__.'/storage', 'storage_path');

        return $instagram;
    }
}
