<?php

namespace Astrotomic\InstagramParser\Contracts;

use Astrotomic\InstagramParser\Manager;

/**
 * @method setConfig($value, $key = null)
 * @method getConfig($key = null, $default = null)
 * @method setClient($value, $key = null)
 * @method getClient($key = null)
 */
class AbstractEndpoint
{
    protected $manager;

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, ['getConfig', 'getClient', 'setConfig', 'setClient'])) {
            return call_user_func_array([$this->manager, $name], $arguments);
        }
        throw new \BadMethodCallException('The method ['.$name.'] does not exist.');
    }
}
