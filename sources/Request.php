<?php

namespace Subway {

    /**
     * @property string $method;
     * @property string $origin;
     * @property string $url;
     * @property string $path;
     * @property array $segments;
     * @property string $query;
     * @property array $keys;
     * @property array $params;
     * @property array $headers;
     * @property array $cookies;
     * @property string $body;
     * @property string $files;
     */

    class Request {

        private string $_method;
        private string $_origin;
        private array $_segments;
        private array $_keys;
        private array $_params;
        private array $_headers;
        private array $_cookies;
        private ?string $_body;
        private array $_files;

        public function __get(string $name) {
            if($name == 'method') return $this->_method;
            if($name == 'origin') return $this->_origin;
            if($name == 'url') return $this->getUrl();
            if($name == 'path') return $this->getPath();
            if($name == 'segments') return $this->_segments;
            if($name == 'query') return $this->getQuery();
            if($name == 'keys') return $this->_keys;
            if($name == 'params') return $this->_params;
            if($name == 'headers') return $this->_headers;
            if($name == 'cookies') return $this->_cookies;
            if($name == 'body') return $this->getBody();
            if($name == 'files') return $this->_files;
            return null;
        }

        public function __construct(string $method, string $url, array $post=[], array $headers=[], array $cookies=[], string | null $body=null, array $files=[]) {
            $this->_method = strtoupper($method) ?? 'GET';
            preg_match("/^(https?:\/{2}(.*?)(\/|$))?(.*?)(\?.*?)?(#.*?)?$/", $url, $parts);
            $parts = $parts ?? [];
            $this->_origin = isset($parts[1]) && $parts[1] ? mb_substr($parts[1], 0, mb_strlen($parts[1]) - 1) : '';
            $this->_segments = Request::parsePath($parts[4] ?? '');
            $this->_keys = Request::parseQuery($parts[5] ?? '');
            $this->_params = $post ?? [];
            $this->_headers = $headers;
            $this->_cookies = $cookies ?? [];
            $this->_body = $body;
            $this->_files = $files ?? [];
        }

        public function segment(int $num) : string {
            $normalized = $num > 0 ? $num - 1 : 0;
            return $this->_segments[$normalized] ?? '';
        }

        public function key(string $name) : string {
            return $this->_keys[$name] ?? '';
        }

        public function param(string $name) : string {
            return $this->_params[$name] ?? '';
        }

        public function header(string $name) : string {
            return $this->_headers[$name] ?? '';
        }

        public function cookie(string $name) : string {
            return $this->_cookies[$name] ?? '';
        }

        public function json(bool $assoc=true) : array {
            return $this->_body ? @json_decode($this->_body, $assoc) : [];
        }

        private function getUrl() : string {
            $query = $this->getQuery();
            $query = $query ? "?$query" : '';
            return "$this->_origin/{$this->getPath()}$query$this->_anchor";
        }

        private function getPath() : string {
            return implode('/', $this->_segments);
        }

        private function getQuery() : string {
            $props = [];
            foreach($this->_keys as $name=>$val) {
                $value = $val ?? '';
                $props[] = "$name=$value";
            }
            return count($props) > 0 ? implode('&', $props) : '';
        }

        private function getBody() : string {
            if($this->_body === null) $this->_body = @file_get_contents('php://input') ?? '';
            return $this->_body;
        }

        public static function getCurrent() : Request {
            $url = 'http';
            if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') $url .= 's';
            $url .= "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
            return new Request(
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $url,
                $_POST ?? [],
                getallheaders() ?? [],
                $_COOKIE ?? [],
                @file_get_contents('php://input') ?? '',
                $_FILES ?? [],
            );
        }

        private static function parsePath(string $path) : array {
            $normalized = trim($path ?: '');
            $normalized = trim($normalized, '/');
            $normalized = preg_replace("/\/+/", '/', $normalized);
            return $normalized ? explode('/', $normalized) : [];
        }

        private static function parseQuery(string $query) : array {
            $normalized = trim($query ?: '');
            $normalized = trim($normalized, "&?");
            $normalized = preg_replace("/&+/", '&', $normalized);
            if(!$normalized) return [];
            $keys = [];
            $props = explode('&', $normalized);
            for($i = 0; $i < count($props); $i++) {
                $temp = explode('=', $props[$i]);
                $keys[$temp[0]] = count($temp) > 2 ? implode('=', array_splice($temp, 1)) : $temp[1] ?? '';
            }
            return $keys;
        }

    }

}