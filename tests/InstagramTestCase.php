<?php

namespace Astrotomic\InstagramParser\Tests;

use Astrotomic\InstagramParser\InstagramParser;
use PHPUnit_Framework_TestCase;

class InstagramTestCase extends PHPUnit_Framework_TestCase
{
    protected function getInstagramParser()
    {
        $instagram = new InstagramParser();
        $instagram->setConfig(__DIR__.'/storage', 'storage_path');

        return $instagram;
    }
}
