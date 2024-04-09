<?php

namespace Subway {

    class Map extends Group {

        private array $_routes = [];
        private $_onFailed;

        public function __construct() {
            parent::__construct('', null);
            $this->_onFailed = function () {};
        }

        public function build() : void {
            $this->_routes = $this->getRoutes();
        }

        public function fallback(callable $onFailed) : void {
            $this->_onFailed = $onFailed;
        }

        public function dispatch(string $method, string $url, array $headers=[]) : void {
            $request = new Request($method, $url, $headers);
            $bestRate = -1;
            $bestRoute = null;
            for($i = 0; $i < count($this->_routes); $i++) {
                $route = $this->_routes[$i];
                $rate = $route->estimate($request);
                if($rate > $bestRate) {
                    $bestRate = $rate;
                    $bestRoute = $route;
                }
            }
            if($bestRoute) {
                $bestRoute->resolve($request);
            } else {
                // two lines coz my IDE highlighted warning if function called directly from a class property, sorry
                $fallback = $this->_onFailed;
                $fallback($request);
            }
        }

    }

}