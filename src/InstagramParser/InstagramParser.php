<?php

namespace Astrotomic\InstagramParser;

class InstagramParser
{
    protected $config = [];
    protected $client = [];

    public function __construct()
    {
        $config = [
            'media_limit'       => 100,
            'cache_time'        => 3600,
            'allowed_usernames' => '*',
            'allowed_tags'      => '*',
            'storage_path'      => __DIR__.'/storage',
        ];
        $client = [
            'base_url'   => 'https://www.instagram.com/',
            'cookie_jar' => [],
            'headers'    => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.87 Safari/537.36',
                'Origin'     => 'https://www.instagram.com',
                'Referer'    => 'https://www.instagram.com',
                'Connection' => 'close',
            ],
        ];
        $this->setConfig($config);
        $this->setClient($client);
    }

    public function setConfig($value, $key = null)
    {
        if (is_null($key)) {
            $this->config = $value;
        }
        $this->config[$key] = $value;
    }

    public function getConfig($key = null)
    {
        if (is_null($key)) {
            return $this->config;
        }

        return $this->config[$key];
    }

    public function setClient($value, $key = null)
    {
        if (is_null($key)) {
            $this->client = $value;
        }
        $this->client[$key] = $value;
    }

    public function getClient($key = null)
    {
        if (is_null($key)) {
            return $this->client;
        }

        return $this->client[$key];
    }

    public function getUserRecentMedia($userName)
    {
        $config = $this->getConfig();
        $mediaLimit = !empty($config['media_limit']) ? $config['media_limit'] : 100;
        $allowedUsernames = !empty($config['allowed_usernames']) ? $config['allowed_usernames'] : '*';
        if (!$this->isAllowed($userName, $allowedUsernames)) {
            throw new \InvalidArgumentException('specified username is not allowed');
        }
        $result = null;
        $dataKey = '@'.$userName;
        $data = $this->getData($dataKey);
        if (is_null($data)) {
            $response = $this->request('get', '/'.$userName.'/');
            if (!$response['status']) {
                throw new \RuntimeException('service is unavailable now');
            } else {
                switch ($response['http_code']) {
                    default:
                        throw new \RuntimeException('service is unavailable now');
                        break;
                    case 404:
                        throw new \InvalidArgumentException('this user does not exist');
                        break;
                    case 200:
                        $sharedJson = [];
                        if (!preg_match('#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#', $response['body'], $sharedJson)) {
                            throw new \RuntimeException('service is unavailable now');
                        } else {
                            $sharedData = json_decode($sharedJson[1], true);
                            if (!$sharedData || empty($sharedData['entry_data']['ProfilePage'][0]['user'])) {
                                throw new \RuntimeException('service is unavailable now');
                            } else {
                                $user = $sharedData['entry_data']['ProfilePage'][0]['user'];
                                if ($user['is_private']) {
                                    throw new \RuntimeException('you cannot view this resource');
                                } else {
                                    $queryResponse = $this->request('post', '/query/', [
                                        'data' => [
                                            'q' => 'ig_user('.$user['id'].') { media.after(0, '.$mediaLimit.') { count, nodes { id, caption, code, comments { count }, date, dimensions { height, width }, filter_name, display_src, id, is_video, likes { count }, owner { id }, thumbnail_src, video_url, location { name, id } }, page_info} }',
                                        ],
                                        'headers' => [
                                            'X-Csrftoken'      => $response['cookies']['csrftoken'],
                                            'X-Requested-With' => 'XMLHttpRequest',
                                            'X-Instagram-Ajax' => '1',
                                        ],
                                    ]);
                                    if ($queryResponse['http_code'] != 200) {
                                        throw new \RuntimeException('service is unavailable now');
                                    } else {
                                        $queryBody = json_decode($queryResponse['body'], true);
                                        if (!$queryBody || empty($queryBody['media']['nodes'])) {
                                            throw new \RuntimeException('service is unavailable now');
                                        } else {
                                            $user['media']['nodes'] = $queryBody['media']['nodes'];
                                            $data = $user;
                                            $this->putData($dataKey, $data);
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
        if (!$data) {
            $data = $this->getData($dataKey, false);
        }
        if ($data) {
            $result = [];
            $formattedUser = [
                'username'        => $data['username'],
                'profile_picture' => $data['profile_pic_url'],
                'id'              => $data['id'],
                'full_name'       => $data['full_name'],
            ];
            foreach ($data['media']['nodes'] as $node) {
                $result[] = $this->parseNode($node, $formattedUser);
            }
        }

        return $result;
    }

    public function getShortcodeMedia($shortcode)
    {
        $result = null;
        $dataKey = '$'.$shortcode;
        $data = $this->getData($dataKey);
        if (is_null($data)) {
            $response = $this->request('get', '/p/'.$shortcode.'/');
            if (!$response['status']) {
                throw new \RuntimeException('service is unavailable now');
            } else {
                switch ($response['http_code']) {
                    default:
                        throw new \RuntimeException('service is unavailable now');
                        break;
                    case 404:
                        throw new \InvalidArgumentException('invalid media shortcode');
                        break;
                    case 200:
                        $sharedJson = [];
                        if (!preg_match('#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#', $response['body'], $sharedJson)) {
                            throw new \RuntimeException('service is unavailable now');
                        } else {
                            $sharedData = json_decode($sharedJson[1], true);
                            if (empty($sharedData['entry_data']['PostPage'][0]['media'])) {
                                throw new \RuntimeException('service is unavailable now');
                            } else {
                                $data = $sharedData['entry_data']['PostPage'][0]['media'];
                                $this->putData($dataKey, $data);
                            }
                        }
                        break;
                }
            }
        }
        if (!$data) {
            $data = $this->getData($dataKey, false);
        }
        if ($data) {
            $result = $this->parseNode($data);
        }

        return $result;
    }

    public function getUser($userName)
    {
        $config = $this->getConfig();
        $mediaLimit = !empty($config['media_limit']) ? $config['media_limit'] : 100;
        $allowedUsernames = !empty($config['allowed_usernames']) ? $config['allowed_usernames'] : '*';
        if (!$this->isAllowed($userName, $allowedUsernames)) {
            throw new \InvalidArgumentException('specified username is not allowed');
        }
        $result = null;
        $dataKey = '@'.$userName;
        $data = $this->getData($dataKey);
        if (is_null($data)) {
            $response = $this->request('get', '/'.$userName.'/');
            if (!$response['status']) {
                throw new \RuntimeException('service is unavailable now');
            } else {
                switch ($response['http_code']) {
                    default:
                        throw new \RuntimeException('service is unavailable now');
                        break;
                    case 404:
                        throw new \RuntimeException('this user does not exist');
                        break;
                    case 200:
                        $sharedJson = [];
                        if (!preg_match('#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#', $response['body'], $sharedJson)) {
                            throw new \RuntimeException('service is unavailable now');
                        } else {
                            $sharedData = json_decode($sharedJson[1], true);
                            if (!$sharedData || empty($sharedData['entry_data']['ProfilePage'][0]['user'])) {
                                throw new \RuntimeException('service is unavailable now');
                            } else {
                                $user = $sharedData['entry_data']['ProfilePage'][0]['user'];
                                if ($user['is_private']) {
                                    throw new \RuntimeException('you can not view this resource');
                                } else {
                                    $queryResponse = $this->request('post', '/query/', ['data' => ['q' => 'ig_user('.$user['id'].') { media.after(0, '.$mediaLimit.') { count, nodes { id, caption, code, comments { count }, date, dimensions { height, width }, filter_name, display_src, id, is_video, likes { count }, owner { id }, thumbnail_src, video_url, location { name, id } }, page_info} }'], 'headers' => ['X-Csrftoken' => $response['cookies']['csrftoken'], 'X-Requested-With' => 'XMLHttpRequest', 'X-Instagram-Ajax' => '1']]);
                                    if ($queryResponse['http_code'] != 200) {
                                        throw new \RuntimeException('service is unavailable now');
                                    } else {
                                        $queryBody = json_decode($queryResponse['body'], true);
                                        if (!$queryBody || empty($queryBody['media']['nodes'])) {
                                            throw new \RuntimeException('service is unavailable now');
                                        } else {
                                            $user['media']['nodes'] = $queryBody['media']['nodes'];
                                            $data = $user;
                                            $this->putData($dataKey, $data);
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
        if (!$data) {
            $data = $this->getData($dataKey, false);
        }
        if ($data) {
            $result = [
                'username'        => $data['username'],
                'profile_picture' => $data['profile_pic_url'],
                'id'              => (int) $data['id'],
                'full_name'       => $data['full_name'],
                'counts'          => [
                    'media'       => (int) $data['media']['count'],
                    'followed_by' => (int) $data['followed_by']['count'],
                    'follows'     => (int) $data['follows']['count'],
                ],
            ];
        }

        return $result;
    }

    public function getTagRecentMedia($tag)
    {
        $config = $this->getConfig();
        $mediaLimit = !empty($config['media_limit']) ? $config['media_limit'] : 100;
        $allowedTags = !empty($config['allowed_tags']) ? $config['allowed_tags'] : '*';
        if (!$this->isAllowed($tag, $allowedTags)) {
            throw new \InvalidArgumentException('specified tag is not allowed');
        }
        $result = null;
        $dataKey = '#'.$tag;
        $data = $this->getData($dataKey);
        if (is_null($data)) {
            $response = $this->request('get', '/explore/tags/'.$tag.'/');
            if (!$response['status']) {
                throw new \RuntimeException('service is unavailable now');
            } else {
                switch ($response['http_code']) {
                    default:
                        throw new \RuntimeException('service is unavailable now');
                        break;
                    case 404:
                        throw new \RuntimeException('invalid media shortcode');
                        break;
                    case 200:
                        $sharedJson = [];
                        if (!preg_match('#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#', $response['body'], $sharedJson)) {
                            throw new \RuntimeException('service is unavailable now');
                        } else {
                            $sharedData = json_decode($sharedJson[1], true);
                            if (!$sharedData || empty($sharedData['entry_data']['TagPage'][0]['tag']['media'])) {
                                throw new \RuntimeException('service is unavailable now');
                            } else {
                                $tagData = $sharedData['entry_data']['TagPage'][0]['tag'];
                                if (!empty($tagData['top_posts']['nodes'])) {
                                    $tagData['media']['nodes'] = $this->getUniqueNodes($tagData['media']['nodes'], $tagData['top_posts']['nodes']);
                                }
                                if (count($tagData['media']['nodes']) > $mediaLimit) {
                                    $tagData['media']['nodes'] = array_slice($tagData['media']['nodes'], 0, $mediaLimit);
                                }
                                $nodeSuccess = true;
                                foreach ($tagData['media']['nodes'] as $key => $node) {
                                    $nodeResponse = $this->request('get', '/p/'.$node['code'].'/');
                                    if ($nodeResponse['http_code'] != 200) {
                                        $nodeSuccess = false;
                                        break;
                                    }
                                    if (!preg_match('#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#', $nodeResponse['body'], $nodeSharedJson)) {
                                        $nodeSuccess = false;
                                        break;
                                    } else {
                                        $nodeSharedData = json_decode($nodeSharedJson[1], true);
                                        if (empty($nodeSharedData['entry_data']['PostPage'][0]['media'])) {
                                            $nodeSuccess = false;
                                            break;
                                        } else {
                                            $tagData['media']['nodes'][$key] = $nodeSharedData['entry_data']['PostPage'][0]['media'];
                                        }
                                    }
                                }
                                unset($key, $node);
                                if (!$nodeSuccess) {
                                    throw new \RuntimeException('service is unavailable now');
                                } else {
                                    $endCursor = $tagData['media']['page_info']['end_cursor'];
                                    $querySuccess = true;
                                    $mediaLimitNextPage = $mediaLimit - 12;
                                    $hasNextPage = $tagData['media']['page_info']['has_next_page'] && $mediaLimitNextPage > 0;
                                    while ($hasNextPage) {
                                        $queryResponse = $this->request('post', '/query/', ['data' => ['q' => 'ig_hashtag('.$tag.') { media.after('.$endCursor.', '.($mediaLimitNextPage > 33 ? 33 : $mediaLimitNextPage).') { count, nodes { id, caption, code, comments { count }, date, dimensions { height, width }, filter_name, display_src, id, is_video, likes { count }, owner { id, username, full_name, profile_pic_url }, thumbnail_src, video_url, location { name, id } }, page_info} }'], 'headers' => ['X-Csrftoken' => $response['cookies']['csrftoken'], 'X-Requested-With' => 'XMLHttpRequest', 'X-Instagram-Ajax' => '1']]);
                                        if ($queryResponse['http_code'] != 200) {
                                            $querySuccess = false;
                                            break;
                                        } else {
                                            $queryBody = json_decode($queryResponse['body'], true);
                                            if (!$queryBody || !isset($queryBody['media']['nodes'])) {
                                                $querySuccess = false;
                                                break;
                                            } else {
                                                $tagData['media']['nodes'] = array_merge($tagData['media']['nodes'], $queryBody['media']['nodes']);
                                                $mediaLimitNextPage -= count($queryBody['media']['nodes']);
                                                $hasNextPage = $queryBody['media']['page_info']['has_next_page'] && $mediaLimitNextPage > 0;
                                                $endCursor = $queryBody['media']['page_info']['end_cursor'];
                                            }
                                        }
                                    }
                                    if (!$querySuccess) {
                                        throw new \RuntimeException('service is unavailable now');
                                    } else {
                                        $data = $tagData;
                                        $this->putData($dataKey, $data);
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }
        if (!$data) {
            $data = $this->getData($dataKey, false);
        }
        if ($data) {
            $result = [];
            foreach ($data['media']['nodes'] as $node) {
                $result[] = $this->parseNode($node);
            }
        }

        return $result;
    }

    protected function isAllowed($userName, $allowedUsernames)
    {
        $allowedUsernames = is_array($allowedUsernames) || is_object($allowedUsernames) ? (array) array_values($allowedUsernames) : explode(',', $allowedUsernames);
        $allowedUsernames = array_map('trim', $allowedUsernames);

        return in_array('*', $allowedUsernames) || in_array($userName, $allowedUsernames);
    }

    protected function getData($key, $validCache = true)
    {
        $cacheTime = $this->getCacheTime();
        $fileName = md5($key);
        $storagePath = $this->getStoragePath();
        $filePath = $storagePath.'/'.$fileName.'.csv';
        if (!is_readable($filePath)) {
            return null;
        }
        $resource = fopen($filePath, 'r');
        $csv = fgetcsv($resource, null, ';');
        if (!$csv || count($csv) != 3 || ($validCache && time() > $csv[1] + $cacheTime)) {
            return null;
        }

        return json_decode($csv[2], true);
    }

    protected function putData($key, $data)
    {
        $fileName = md5($key);
        $storagePath = $this->getStoragePath();
        $filePath = $storagePath.'/'.$fileName.'.csv';
        if (!is_dir($storagePath) && !@mkdir($storagePath, 0775, true)) {
            return false;
        }
        $resource = fopen($filePath, 'w');
        fputcsv($resource, [$key, time(), json_encode($data)], ';');
        fclose($resource);

        return true;
    }

    protected function getCacheTime()
    {
        $cacheTime = (int) $this->getConfig('cache_time');

        return ($cacheTime > 0) ? $cacheTime : 3600;
    }

    protected function getStoragePath()
    {
        return rtrim($this->getConfig('storage_path'), '/');
    }

    protected function request($method, $url, $meta = null)
    {
        $client = $this->getClient();
        $method = strtoupper($method);
        $meta = is_array($meta) ? $meta : [];
        $url = (!empty($client['base_url']) ? rtrim($client['base_url'], '/') : '').$url;
        $parsedUrl = parse_url($url);
        $schema = !empty($parsedUrl['scheme']) ? $parsedUrl['scheme'] : '';
        $host = !empty($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = !empty($parsedUrl['port']) ? $parsedUrl['port'] : '';
        $path = !empty($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $headers = !empty($client['headers']) ? $client['headers'] : [];
        if (!empty($meta['headers'])) {
            $headers = $this->mergeArrays($headers, $meta['headers']);
        }
        $headers['Host'] = $host;
        $baseCookies = $this->getCookies($host);
        $metaCookies = $baseCookies;
        if (!empty($meta['cookies'])) {
            $metaCookies = $this->mergeArrays($metaCookies, $meta['cookies']);
        }
        if ($metaCookies) {
            $headerCookies = [];
            foreach ($metaCookies as $cookieName => $cookieValue) {
                $headerCookies[] = $cookieName.'='.$cookieValue;
            }
            unset($cookieName, $cookieValue);
            $headers['Cookie'] = implode('; ', $headerCookies);
        }
        if ($method === 'POST' && !empty($meta['data'])) {
            $postFields = http_build_query($meta['data']);
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $headers['Content-Length'] = strlen($postFields);
        } else {
            $postFields = '';
        }
        $httpHeader = [];
        foreach ($headers as $headerName => $headerValue) {
            $httpHeader[] = $headerName.': '.$headerValue;
        }
        unset($headerName, $headerValue);
        $wLtvZaiXEoqQBgJVdhac = null;
        $curlExists = function_exists('curl_init');
        $curlErrored = false;
        $socketExists = function_exists('fsockopen');
        if (!$curlExists && !$socketExists) {
            throw new \RuntimeException('curl and sockets are not supported on this server');
        }

        if ($curlExists) {
            $curl = curl_init();
            $curl_options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => true,
                CURLOPT_URL            => $schema.'://'.$host.$path,
                CURLOPT_HTTPHEADER     => $httpHeader,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 15,
            ];
            if ($method === 'POST') {
                $curl_options[CURLOPT_POST] = true;
                $curl_options[CURLOPT_POSTFIELDS] = $postFields;
            }
            curl_setopt_array($curl, $curl_options);
            $result = curl_exec($curl);
            $curlInfo = curl_getinfo($curl);
            $curlError = curl_error($curl);
            curl_close($curl);
            if ($curlInfo['http_code'] === 0) {
                $curlErrored = true;
                if (!$socketExists) {
                    throw new \RuntimeException("curl request failed with error: {$curlError}");
                }
            }
        }

        if ((!$curlExists || $curlErrored) && $socketExists) {
            $socketHeader = implode("\r\n", $httpHeader);
            $socketString = sprintf("%s %s HTTP/1.1\r\n%s\r\n\r\n%s", $method, $path, $socketHeader, $postFields);
            if ($schema === 'https') {
                $schema = 'ssl';
                $port = !empty($port) ? $port : 443;
            }
            $schema = !empty($schema) ? $schema.'://' : '';
            $port = !empty($port) ? $port : 80;
            $socket = @fsockopen($schema.$host, $port, $errorNo, $errorMsg, 15);
            if (!$socket) {
                throw new \RuntimeException('An error occurred while loading data error_number: '.$errorNo.', error_message: '.$errorMsg);
            }
            fwrite($socket, $socketString);
            $result = '';
            while ($buffer = fgets($socket, 128)) {
                $result .= $buffer;
            }
            fclose($socket);
        }

        if (!isset($result)) {
            throw new \RuntimeException('was not able to fetch a result');
        }

        list($head, $body) = explode("\r\n\r\n", $result);
        $headLines = explode("\r\n", $head);
        $firstHeadLine = array_shift($headLines);
        preg_match('#^([^\s]+)\s(\d+)\s([^$]+)$#', $firstHeadLine, $headerHttpInfos);
        array_shift($headerHttpInfos);
        list($httpProtocol, $httpCode, $httpMessage) = $headerHttpInfos;
        $responseHeaders = [];
        $responseCookies = [];
        foreach ($headLines as $headLine) {
            list($headerName, $headerValue) = explode(': ', $headLine);
            if (strtolower($headerName) === 'set-cookie') {
                $responseHeaderCookies = explode('; ', $headerValue);
                if (empty($responseHeaderCookies[0])) {
                    continue;
                }
                list($cookieName, $cookieValue) = explode('=', $responseHeaderCookies[0]);
                $responseCookies[$cookieName] = $cookieValue;
            } else {
                $responseHeaders[$headerName] = $headerValue;
            }
        }
        unset($headLine, $headerName, $headerValue, $cookieName, $cookieValue);
        if ($responseCookies) {
            $client['cookie_jar'][$host] = $this->mergeArrays($baseCookies, $responseCookies);
            $this->setClient($client);
        }

        return [
            'status'        => 1,
            'http_protocol' => $httpProtocol,
            'http_code'     => $httpCode,
            'http_message'  => $httpMessage,
            'headers'       => $responseHeaders,
            'cookies'       => $responseCookies,
            'body'          => $body,
        ];
    }

    protected function mergeArrays()
    {
        $result = null;
        $arrays = func_get_args();
        foreach ($arrays as $key => $array) {
            if ($key === 0) {
                $result = $array;
                continue;
            }
            $result = array_combine(
                array_merge(array_keys($result), array_keys($array)),
                array_merge(array_values($result), array_values($array))
            );
        }

        return $result;
    }

    protected function getCookies($host)
    {
        $cookies = $this->getClient('cookie_jar');

        return !empty($cookies[$host]) ? $cookies[$host] : [];
    }

    protected function parseNode($node, $formattedUser = null)
    {
        $formattedUser = !empty($formattedUser) ? $formattedUser : null;
        if (!empty($node['owner']) && is_null($formattedUser)) {
            $formattedUser = [
                'username'        => $node['owner']['username'],
                'profile_picture' => $node['owner']['profile_pic_url'],
                'id'              => $node['owner']['id'],
                'full_name'       => $node['owner']['full_name'],
            ];
        }

        $aspectRatio = $node['dimensions']['height'] / $node['dimensions']['width'];

        $media = [
            'attribution'  => null,
            'videos'       => null,
            'tags'         => null,
            'location'     => null,
            'comments'     => null,
            'filter'       => !empty($node['filter_name']) ? $node['filter_name'] : null,
            'created_time' => $node['date'],
            'link'         => 'https://www.instagram.com/p/'.$node['code'].'/',
            'likes'        => null,
            'images'       => [
                'low_resolution' => [
                    'url'    => $this->getDisplaySrcBySize($node['display_src'], 320, 320),
                    'width'  => 320,
                    'height' => $aspectRatio * 320,
                ],
                'standard_resolution' => [
                    'url'    => $this->getDisplaySrcBySize($node['display_src'], 640, 640),
                    'width'  => 640,
                    'height' => $aspectRatio * 640,
                ],
                '__original' => [
                    'url'    => $node['display_src'],
                    'width'  => $node['dimensions']['width'],
                    'height' => $node['dimensions']['height'],
                ],
            ],
            'users_in_photo' => null,
            'caption'        => null,
            'type'           => $node['is_video'] ? 'video' : 'image',
            'id'             => $node['id'].'_'.$formattedUser['id'],
            'code'           => $node['code'],
            'user'           => $formattedUser,
        ];
        if(array_key_exists('thumbnail_src', $node)) {
            $media['images']['thumbnail'] = [
                'url'    => $node['thumbnail_src'],
                'width'  => 640,
                'height' => 640,
            ];
        }

        if (!empty($node['caption'])) {
            $media['caption'] = [
                'created_time' => $node['date'],
                'text'         => $node['caption'],
                'from'         => $formattedUser,
            ];
            $media['tags'] = $this->parseTags($node['caption']);
        }

        if (!empty($node['video_url'])) {
            $media['videos'] = [
                'standard_resolution' => [
                    'url'    => $node['video_url'],
                    'width'  => 640,
                    'height' => $aspectRatio * 640,
                ],
            ];
        }

        if (!empty($node['comments'])) {
            $media['comments'] = [
                'count' => !empty($node['comments']['count']) ? $node['comments']['count'] : 0,
                'data'  => [],
            ];

            if (!empty($node['comments']['nodes'])) {
                $comments = $node['comments']['nodes'];
                foreach ($comments as $comment) {
                    $commentUser = null;
                    if (!empty($comment['user'])) {
                        $commentUser = [
                            'username'        => $comment['user']['username'],
                            'profile_picture' => $comment['user']['profile_pic_url'],
                            'id'              => $comment['user']['id'],
                        ];
                    }
                    $media['comments']['data'][] = [
                        'created_time' => $comment['created_at'],
                        'text'         => $comment['text'],
                        'from'         => $commentUser,
                    ];
                }
            }
        }
        if (!empty($node['likes'])) {
            $media['likes'] = [
                'count' => !empty($node['likes']['count']) ? $node['likes']['count'] : 0,
                'data'  => [],
            ];
            if (!empty($node['likes']['nodes'])) {
                $likes = $node['likes']['nodes'];
                foreach ($likes as $like) {
                    $likeUser = null;
                    if (!empty($like['user'])) {
                        $likeUser = [
                            'username'        => $like['user']['username'],
                            'profile_picture' => $like['user']['profile_pic_url'],
                            'id'              => $like['user']['id'],
                        ];
                    }
                    $media['likes']['data'][] = $likeUser;
                }
            }
        }
        if (!empty($node['location'])) {
            $media['location'] = [
                'name' => $node['location']['name'],
                'id'   => $node['location']['id'],
            ];
        }

        return $media;
    }

    protected function getDisplaySrcBySize($displaySrc, $width, $height)
    {
        if (preg_match('#/s\d+x\d+/#', $displaySrc)) {
            return preg_replace('#/s\d+x\d+/#', '/s'.$width.'x'.$height.'/', $displaySrc);
        } elseif (preg_match('#/e\d+/#', $displaySrc)) {
            return preg_replace('#/e(\d+)/#', '/s'.$width.'x'.$height.'/e$1/', $displaySrc);
        } elseif (preg_match('#(\.com/[^/]+)/#', $displaySrc)) {
            return preg_replace('#(\.com/[^/]+)/#', '$1/s'.$width.'x'.$height.'/', $displaySrc);
        }

        return null;
    }

    protected function parseTags($caption)
    {
        preg_match_all('#\#([\w_]+)#u', $caption, $tags);

        return $tags[1];
    }

    protected function getUniqueNodes()
    {
        $result = [];
        $nodeHolder = func_get_args();
        foreach ($nodeHolder as $nodes) {
            foreach ($nodes as $node) {
                $result[$node['code']] = $node;
            }
        }

        return array_values($result);
    }
}
