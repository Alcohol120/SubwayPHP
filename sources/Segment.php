<?php

namespace Subway {

    /**
     * @property ESegmentType $type
     * @property string $name
     * @property string | null $pattern
     * @property bool $optional
     */

    class Segment {

        private ESegmentType $_type;
        private string $_name;
        private string | null $_pattern;
        private bool $_optional;

        public function __get(string $name) {
            if($name == 'type') return $this->_type;
            if($name == 'name') return $this->_name;
            if($name == 'pattern') return $this->_pattern;
            if($name == 'optional') return $this->_optional;
            return null;
        }

        public function __construct(string $source) {
            $this->_type = Segment::getType($source);
            $this->_name = Segment::getName($source);
            $this->_pattern = Segment::getPattern($source);
            $this->_optional = Segment::getOptional($source);
        }

        public function estimate(string $segment) : int {
            $rate = -1;
            if($this->_type == ESegmentType::COMMON) {
                $rate = $segment == $this->_name ? 3 : -1;
            } elseif($this->_type == ESegmentType::ANY) {
                $rate = $segment ? 1 : -1;
            } elseif($this->_type == ESegmentType::ALPHA) {
                $rate = preg_match("/^[a-z\-_]+$/i", $segment) ? 2 : -1;
            } elseif($this->_type == ESegmentType::INTEGER) {
                $rate = preg_match("/^\d+$/", $segment) ? 2 : -1;
            } elseif($this->_type == ESegmentType::PATTERN) {
                $rate = preg_match($this->_pattern, $segment) ? 2 : -1;
            }
            if($rate < 0 && $this->_optional) $rate = 0;
            return $rate;
        }

        private static function getType(string $source) : ESegmentType {
            if(!preg_match("/^{.+?}\??$/", $source)) {
                return ESegmentType::COMMON;
            } elseif(!preg_match("/^{.+?:.+?}\??$/", $source)) {
                return ESegmentType::ANY;
            } elseif(preg_match("/^{.+?:a}\??$/", $source)) {
                return ESegmentType::ALPHA;
            } elseif(preg_match("/^{.+?:i}\??$/", $source)) {
                return ESegmentType::INTEGER;
            } else return ESegmentType::PATTERN;
        }

        private static function getName(string $source) : string {
            if(!preg_match("/^{?(.+?)(:.+?)?}?\??$/", $source, $matches)) return '';
            return $matches[1] ?? '';
        }

        private static function getPattern(string $source) : string|null {
            $res = preg_match("/^{.+?:(.+?)}\??$/", $source, $matches);
            if($res && !in_array($matches[1], ['a', 'i'])) {
                return "/$matches[1]/i";
            } else return null;
        }

        private static function getOptional(string $source) : bool {
            return !!preg_match("/\?$/", $source);
        }

    }

}