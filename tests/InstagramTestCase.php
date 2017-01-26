<?php
namespace Astrotomic\InstagramParser\Tests;

use PHPUnit_Framework_TestCase;
use Astrotomic\InstagramParser\InstagramParser;

class InstagramTestCase extends PHPUnit_Framework_TestCase
{
    protected function getInstagramParser()
    {
        $instagram = new InstagramParser();
        $instagram->setConfig(__DIR__.'/storage', 'storage_path');
        return $instagram;
    }
}