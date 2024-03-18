<?php

namespace Subway {

    class Group extends Member {

        protected array $_members = [];

        public function __construct(string $path, callable | null $children) {
            parent::__construct($path);
            if($children) $children($this);
        }

        public function group(string $path, callable $onLoad) : Group {
            $group = new Group($path, $onLoad);
            $this->_members[] = $group;
            return $group;
        }

        public function route(string $path, callable $onLoad) : Endpoint {
            $endpoint = new Endpoint($path, $onLoad);
            $this->_members[] = $endpoint;
            return $endpoint;
        }

        public function getRoutes(array | null $parentProps = null) : array {
            $routes = [];
            $props = $this->joinProps($parentProps);
            for($i = 0; $i < count($this->_members); $i++) {
                $member = $this->_members[$i];
                if($member instanceof Group) {
                    $routes = [ ...$routes, ...$member->getRoutes($props) ];
                } elseif($member instanceof Endpoint) {
                    $routes[] = $member->getRoute($props);
                }
            }
            return $routes;
        }

        private function joinProps(array | null $parentProps = null) : array {
            return [
                'groups' => [ ...$parentProps ? $parentProps['groups'] : [], ...$this->_name ? [ $this->_name ] : [] ],
                'segments' => [ ...$parentProps ? $parentProps['segments'] : [], ...$this->_segments ],
                'middleware' => [ ...$parentProps ? $parentProps['middleware'] : [], ...$this->_middleware ],
            ];
        }

    }

}