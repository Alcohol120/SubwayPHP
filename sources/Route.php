<?php

namespace Subway {

    /**
     * @property string $method;
     * @property string $name;
     * @property array $groups;
     */

    class Route {

        private string $_method = '';
        private string $_name = '';
        private array $_groups = [];
        private array $_segments = [];
        private array $_middleware = [];
        private $_onLoad;

        public function __get(string $name) {
            if($name == 'method') return $this->_method;
            if($name == 'name') return $this->_name;
            if($name == 'groups') return $this->_groups;
            return null;
        }

        public function __construct(string $method, string $name, array $groups, array $segments, array $middleware, callable $onLoad) {
            $this->_method = strtoupper($method);
            $this->_name = $name;
            $this->_groups = $groups;
            $this->_segments = $segments;
            $this->_middleware = $middleware;
            $this->_onLoad = $onLoad;
        }

        public function inGroup(string ...$groups) : bool {
            for($i = 0; $i < count($groups); $i++) if(!in_array($groups[$i], $this->_groups)) return false;
            return true;
        }

        public function estimate(Request $request) : int {
            if($request->method != $this->_method) return -1;
            $paths = $request->segments;
            $rates = array_fill(0, count($this->_segments), 0);
            $pathIndex = 0;
            $optionals = 0;
            $backwards = 0;
            for($i = 0; $i < count($rates); $i++) {
                while(true) {
                    $path = $paths[$pathIndex] ?? '';
                    $segment = $this->_segments[$i];
                    $rate = $segment->estimate($path);
                    if($rate <= 0 && $optionals > 0) {
                        // backward to previous paths that was rated by previous optional segments
                        $optionals--;
                        $backwards++;
                        $pathIndex--;
                        continue;
                    } else if($rate <= 0 && $backwards > 0) {
                        // reverse lookup failed
                        // break all actions if this is a required segment
                        if($rate < 0) return -1;
                        // restore counters and go to the next segment if current is optional
                        $pathIndex += $backwards;
                        $optionals += $backwards;
                        $backwards = 0;
                    } else if($rate > 0 && $backwards > 0) {
                        // reverse lookup success, reset rates of a skipped optional segments
                        for($b = 1; $b <= $backwards; $b++) $rates[$i - $b] = 0;
                        $backwards = 0;
                    }
                    if($rate < 0) {
                        // estimation failed, reverse lookup is not possible or not efficient
                        return -1;
                    }
                    if($segment->optional) {
                        $optionals++;
                    } else $optionals = 0;
                    $rates[$i] = $rate;
                    break;
                }
                $pathIndex++;
            }
            // calculate summary rate
            $rate = 0;
            for($i = 0; $i < count($rates); $i++) $rate += $rates[$i];
            for($i = 0; $i < count($this->_middleware); $i++) {
                if(!method_exists($this->_middleware[$i], 'onEstimated')) continue;
                $rate = $this->_middleware[$i]->onEstimated($rate, $request, $this);
            }
            return $rate;
        }

        public function getUrl(array $props = []) : string | null {
            $paths = [];
            for($i = 0; $i < count($this->_segments); $i++) {
                $segment = $this->_segments[$i];
                if($segment->optional && !isset($props[$segment->name])) continue;
                if($segment->type == ESegmentType::COMMON) {
                    $paths[] = $segment->name;
                    continue;
                }
                $prop = $props[$segment->name] ?? '';
                if($segment->type == ESegmentType::ALPHA) {
                    if(!preg_match("/^[a-z\-_]+$/i", $prop)) return null;
                    $paths[] = $prop;
                } elseif($segment->type == ESegmentType::INTEGER) {
                    if(!preg_match("/^\d+$/", $prop)) return null;
                    $paths[] = $prop;
                } elseif($segment->type == ESegmentType::PATTERN) {
                    if(!preg_match($segment->pattern, $prop)) return null;
                    $paths[] = $prop;
                } elseif($segment->type == ESegmentType::ANY) {
                    if(!$prop) return null;
                    $paths[] = $prop;
                }
            }
            return '/' . implode('/', $paths);
        }

        public function resolve(Request $request) : void {
            $response = new Response();
            $onLoad = $this->_onLoad;
            for($i = 0; $i < count($this->_middleware); $i++) {
                if(!method_exists($this->_middleware[$i], 'onResolving')) continue;
                $onLoad = $this->_middleware[$i]->onResolving($onLoad, $request, $response, $this);
            }
            $result = $onLoad($request, $response, $this);
            for($i = 0; $i < count($this->_middleware); $i++) {
                if(!method_exists($this->_middleware[$i], 'onResolved')) continue;
                $this->_middleware[$i]->onResolved($request, $result instanceof Response ? $result : $response, $this);
            }
            if($result instanceof Response) {
                if($result->json) $result->header('Content-Type', 'application/json');
                http_response_code($result->status);
                foreach($result->headers as $key=>$val) {
                    header("{$key}:{$val}");
                }
                if($result->body) {
                    echo $result->body;
                } elseif($result->json) {
                    echo json_encode($result->json);
                } else echo '';
            }
        }

    }

}