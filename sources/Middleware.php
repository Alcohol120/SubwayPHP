<?php

namespace Subway {

    abstract class Middleware {

        public function onEstimated(int $rate, Request $request, Route $route) : int {
            return $rate;
        }

        public function onResolving(callable $onLoad, Request $request, Response $response, Route $route) : callable {
            return $onLoad;
        }

        public function onResolved(Request $request, Response $response, Route $route) : void {}

    }

}