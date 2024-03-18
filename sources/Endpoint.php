<?php

namespace Subway {

    class Endpoint extends Member {

        private $_onLoad;

        public function __construct(string $path, callable $onLoad) {
            parent::__construct($path);
            $this->_onLoad = $onLoad;
        }

        public function getRoute(array | null $parentProps = null) : Route {
            $props = $this->joinProps($parentProps);
            return new Route($props['name'], $props['groups'], $props['segments'], $props['middleware'], $this->_onLoad);
        }

        private function joinProps(array | null $parentProps = null) : array {
            return [
                'name' => $this->_name,
                'groups' => $parentProps ? $parentProps['groups'] : [],
                'segments' => [ ...$parentProps ? $parentProps['segments'] : [], ...$this->_segments ],
                'middleware' => [ ...$parentProps ? $parentProps['middleware'] : [], ...$this->_middleware ],
            ];
        }

    }

}