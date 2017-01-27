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

    public function testUnallowedUserException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('specified username is not allowed');

        $parser = $this->getInstagramParser();
        $parser->setConfig('just.this.guy', 'allowed_usernames');
        $parser->getUserRecentMedia($this->user);
    }

    public function testUnknownUserException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('this user does not exist');

        $parser = $this->getInstagramParser();
        $parser->getUserRecentMedia(md5($this->user));
    }
}
