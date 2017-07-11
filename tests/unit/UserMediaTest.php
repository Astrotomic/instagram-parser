<?php

namespace Astrotomic\InstagramParser\Tests;

class UserMediaTest extends InstagramTestCase
{
    protected $user = 'dev.gummibeer';

    public function testSuccess()
    {
        $parser = $this->getInstagramParser();
        $data = $parser->getUserRecentMedia($this->user);

        $this->assertInternalType('array', $data);
    }

    public function testUnknownException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('there are no results for this query');

        $parser = $this->getInstagramParser();
        $parser->getUserRecentMedia(md5($this->user));
    }
}
