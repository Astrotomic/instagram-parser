<?php

namespace Astrotomic\InstagramParser\Tests;

class UserTest extends InstagramTestCase
{
    protected $user = 'dev.gummibeer';

    public function testSuccess()
    {
        $parser = $this->getInstagramParser();
        $data = $parser->getUser($this->user);

        $this->assertInternalType('array', $data);

        $this->assertArrayHasKey('id', $data);
        $this->assertInternalType('int', $data['id']);

        $this->assertArrayHasKey('username', $data);
        $this->assertInternalType('string', $data['username']);
        $this->assertSame($this->user, $data['username']);

        $this->assertArrayHasKey('full_name', $data);
        $this->assertInternalType('string', $data['full_name']);

        $this->assertArrayHasKey('profile_picture', $data);
        $this->assertInternalType('string', $data['profile_picture']);

        $this->assertArrayHasKey('counts', $data);

        $this->assertInternalType('array', $data['counts']);

        $this->assertArrayHasKey('media', $data['counts']);
        $this->assertInternalType('int', $data['counts']['media']);

        $this->assertArrayHasKey('followed_by', $data['counts']);
        $this->assertInternalType('int', $data['counts']['followed_by']);

        $this->assertArrayHasKey('follows', $data['counts']);
        $this->assertInternalType('int', $data['counts']['follows']);
    }

    public function testUnallowedUserException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('specified username is not allowed');

        $parser = $this->getInstagramParser();
        $parser->setConfig('just.this.guy', 'allowed_usernames');
        $parser->getUser($this->user);
    }

    public function testUnknownUserException()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('this user does not exist');

        $parser = $this->getInstagramParser();
        $parser->getUser(md5($this->user));
    }
}
