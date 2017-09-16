<?php

trait Helper {
	protected static function prepend($array, $value, $key = null) {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }
	
	protected static function snake($value, $delimiter = '_') {
		return Base::instance()->snakecase($value);
	}
	
	protected static function studly($value) {
        $key = $value;
        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        return str_replace(' ', '', $value);
    }
	
	protected static function is($pattern, $value) {
        $patterns = is_array($pattern) ? $pattern : (array) $pattern;

        if (empty($patterns)) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($pattern == $value) {
                return true;
            }

            $pattern = preg_quote($pattern, '#');
            $pattern = str_replace('\*', '.*', $pattern);

            if (preg_match('#^'.$pattern.'\z#u', $value) === 1) {
                return true;
            }
        }

        return false;
    }
	
	protected static function parseCallback($callback, $default = null) {
        return $this->contains($callback, '@') ? explode('@', $callback, 2) : [$callback, $default];
    }
	
	protected static function lower($value) {
        return mb_strtolower($value, 'UTF-8');
    }
	
	protected static function contains($haystack, $needles) {
        foreach ((array) $needles as $needle) {
            if ($needle !== '' && mb_strpos($haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}