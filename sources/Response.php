<?php

namespace Subway {

    /**
     * @property int $status;
     * @property array $headers;
     */

    class Response {

        private int $_statusCode = 200;
        private array $_headers = [];

        public function __get(string $name) {
            if($name == 'status') return $this->_statusCode;
            if($name == 'headers') return $this->_headers;
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

        public function sendText(string $data) : void {
            $this->sendHeaders();
            echo $data;
        }

        public function sendJSON(array $data) : void {
            $this->header('Content-Type', 'application/json');
            $this->sendHeaders();
            echo json_encode($data);
        }

        public function redirect(string $url, int $statusCode=302) : void {
            $this->_statusCode = $statusCode;
            header('Location', $url, $statusCode);
        }

        private function sendHeaders() : void {
            http_response_code($this->_statusCode);
            foreach($this->_headers as $key=>$val) {
                header("{$key}:{$val}");
            }
        }

    }

}