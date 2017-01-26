<?php
namespace Astrotomic\InstagramParser\Tests;

class ShortcodeMediaTest extends InstagramTestCase
{
    protected $shortcode = 'BMpbpm1hMGs';

    public function testSuccess()
    {
        $parser = $this->getInstagramParser();
        $data = $parser->getShortcodeMedia($this->shortcode);
        
        $this->assertInternalType('array', $data);
    }

    public function testUnknownUserException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid media shortcode');

        $parser = $this->getInstagramParser();
        $parser->getShortcodeMedia(md5($this->shortcode));
    }
}