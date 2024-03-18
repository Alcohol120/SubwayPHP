<?php

namespace Subway {

    interface IMiddleware {

        public function onEstimated(int $rate, Request $request, Route $route) : int;

        public function onResolving(callable $onLoad, Request $request, Route $route) : callable;

        public function onResolved(Request $request, Route $route) : void;

    }

    abstract class Middleware implements IMiddleware {

        public function onEstimated(int $rate, Request $request, Route $route) : int {
            return $rate;
        }

        public function onResolving(callable $onLoad, Request $request, Route $route) : callable {
            return $onLoad;
        }

        public function onResolved(Request $request, Route $route) : void {}

    }

}