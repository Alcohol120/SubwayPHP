<?php

namespace Subway {

    /**
     * @property int $status;
     * @property array $headers;
     * @property ?string $body;
     * @property ?array $json;
     */

    class Response {

        private int $_statusCode = 200;
        private array $_headers = [];
        private ?string $_body = null;
        private ?array $_json = null;

        public function __get(string $name) {
            if($name == 'status') return $this->_statusCode;
            if($name == 'headers') return $this->_headers;
            if($name == 'body') return $this->_body;
            if($name == 'json') return $this->_json;
            return null;
        }

        public function status(int $statusCode) : Response {
            $this->_statusCode = $statusCode;
            return $this;
        }

        public function header(string $name, string $value) : Response {
            $this->_headers[$name] = $value;
            return $this;
        }

        public function body(string $body) : Response {
            $this->_body = $body;
            $this->_json = null;
            return $this;
        }

        public function json(array $json) : Response {
            $this->_body = null;
            $this->_json = $json;
            return $this;
        }

        public function redirect(string $url, int $statusCode=302) : void {
            $this->_statusCode = $statusCode;
            $this->header('Location', $url);
        }

    }

}