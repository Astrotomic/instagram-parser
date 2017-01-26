<?php

namespace Astrotomic\InstagramParser\Tests;

class TagMediaTest extends InstagramTestCase
{
    protected $tag = 'instagram';

    public function testSuccess()
    {
        $parser = $this->getInstagramParser();
        $data = $parser->getTagRecentMedia($this->tag);

        $this->assertInternalType('array', $data);
    }
}
