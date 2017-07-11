<?php

namespace Astrotomic\InstagramParser\Endpoints;

use Astrotomic\InstagramParser\Contracts\AbstractEndpoint;
use Astrotomic\InstagramParser\Contracts\Endpoint;
use Astrotomic\InstagramParser\Traits\Cacheable;
use Astrotomic\InstagramParser\Traits\NodeParser;
use Astrotomic\InstagramParser\Traits\Requester;

class UserRecentMedia extends AbstractEndpoint implements Endpoint
{
    use Cacheable, Requester, NodeParser;

    public function handle($query)
    {
        $userName = $query;
        $mediaLimit = $this->getConfig('media_limit', 100);
        $result = null;
        $dataKey = '@'.$userName;
        $data = $this->getData($dataKey);
        if (is_null($data)) {
            $data = $this->requestData('get', '/'.$userName.'/', 'ProfilePage', 'user', $mediaLimit);
            $this->putData($dataKey, $data);
        }
        if (!$data) {
            $data = $this->getData($dataKey, false);
        }
        if ($data) {
            $result = [];
            foreach ($data as $node) {
                $result[] = $this->parseNode($node);
            }
        }

        return $result;
    }
}
