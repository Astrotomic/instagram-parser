<?php

namespace Astrotomic\InstagramParser\Traits;

trait Requester
{
    protected function requestData($method, $url, $pageKey, $typeKey, $limit = null)
    {
        $maxId = '';
        $nodes = [];
        do {
            $requestUrl = $url.'?max_id='.$maxId;
            $response = $this->request($method, $requestUrl);
            $sharedData = $this->getSharedData($response);
            $media = $sharedData['entry_data'][$pageKey][0][$typeKey]['media'];
            $nodes = array_merge($nodes, $media['nodes']);
            $hasNextPage = $maxId != end($nodes)['id'];
            $maxId = end($nodes)['id'];
        } while ($hasNextPage && count($nodes) < $limit);

        return $nodes;
    }

    protected function getSharedData($response)
    {
        if (!$response['status']) {
            throw new \RuntimeException('service is unavailable now');
        }
        switch ($response['http_code']) {
            default:
                throw new \RuntimeException('service is unavailable now');
                break;
            case 404:
                throw new \InvalidArgumentException('there are no results for this query');
                break;
            case 200:
                $sharedJson = [];
                if (!preg_match('#window\._sharedData\s*=\s*(.*?)\s*;\s*</script>#', $response['body'], $sharedJson)) {
                    throw new \RuntimeException('service is unavailable now');
                }

                return json_decode($sharedJson[1], true);
                break;
        }
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
        $query = !empty($parsedUrl['query']) ? $parsedUrl['query'] : '';
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
        $postFields = '';
        if ($method === 'POST' && !empty($meta['data'])) {
            $postFields = http_build_query($meta['data']);
            $headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $headers['Content-Length'] = strlen($postFields);
        }
        $httpHeader = [];
        foreach ($headers as $headerName => $headerValue) {
            $httpHeader[] = $headerName.': '.$headerValue;
        }
        unset($headerName, $headerValue);
        $curlExists = function_exists('curl_init');
        $curlErrored = false;
        $socketExists = function_exists('fsockopen');
        if (!$curlExists && !$socketExists) {
            throw new \RuntimeException('curl and sockets are not supported on this server');
        }

        if ($curlExists) {
            $curl = curl_init();
            $curlOptions = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => true,
                CURLOPT_URL            => $schema.'://'.$host.$path.'?'.$query,
                CURLOPT_HTTPHEADER     => $httpHeader,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 15,
            ];
            if ($method === 'POST') {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = $postFields;
            }
            curl_setopt_array($curl, $curlOptions);
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
            $responseHeaders[$headerName] = $headerValue;
            if (strtolower($headerName) === 'set-cookie') {
                $responseHeaderCookies = explode('; ', $headerValue);
                if (empty($responseHeaderCookies[0])) {
                    continue;
                }
                list($cookieName, $cookieValue) = explode('=', $responseHeaderCookies[0]);
                $responseCookies[$cookieName] = $cookieValue;
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
}
