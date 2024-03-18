<?php

namespace Subway {

    abstract class Member {

        protected string $_name = '';
        protected array $_segments = [];
        protected array $_middleware = [];

        protected function __construct(string $path) {
            $this->fill($path);
        }

        public function name(string $name) : Member {
            $this->_name = $name;
            return $this;
        }

        public function middleware(Middleware ...$middleware) : Member {
            $this->_middleware = [ ...$this->_middleware, ...$middleware ];
            return $this;
        }

        private function fill(string $path) : void {
            $segments = explode('/', Member::clearPath($path));
            for($i = 0; $i < count($segments); $i++) {
                if(!$segments[$i]) continue;
                $this->_segments[] = new Segment($segments[$i]);
            }
        }

        protected static function clearPath(string $path) : string {
            $normalized = trim($path);
            $normalized = trim($normalized, '/');
            return preg_replace("/\/+/", '/', $normalized);
        }

    }

}