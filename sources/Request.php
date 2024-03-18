<?php

namespace Subway {

    /**
     * @property string $origin;
     * @property string $url;
     * @property string $path;
     * @property array $segments;
     * @property string $query;
     * @property array $keys;
     * @property string $anchor;
     */

    class Request {

        private string $_origin;
        private array $_segments;
        private array $_keys;
        private string $_anchor;

        public function __get(string $name) {
            if($name == 'origin') return $this->_origin;
            if($name == 'url') return $this->getUrl();
            if($name == 'path') return $this->getPath();
            if($name == 'segments') return $this->_segments;
            if($name == 'query') return $this->getQuery();
            if($name == 'keys') return $this->_keys;
            if($name == 'anchor') return $this->_anchor;
            return null;
        }

        public function __construct(string $url) {
            preg_match("/^(https?:\/{2}(.*?)(\/|$))?(.*?)(\?.*?)?(#.*?)?$/", $url, $parts);
            $parts = $parts ?? [];
            $this->_origin = isset($parts[1]) && $parts[1] ? mb_substr($parts[1], 0, mb_strlen($parts[1]) - 1) : '';
            $this->_segments = Request::parsePath($parts[4] ?? '');
            $this->_keys = Request::parseQuery($parts[5] ?? '');
            $this->_anchor = $parts[6] ?? '';
        }

        public function segment(int $num) : string {
            $normalized = $num > 0 ? $num - 1 : 0;
            return $this->_segments[$normalized] ?? '';
        }

        public function key(string $name) : string {
            return $this->_keys[$name] ?? '';
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