<?php
namespace Astrotomic\InstagramParser\Contracts;

interface Endpoint
{
    public function handle($query);
}