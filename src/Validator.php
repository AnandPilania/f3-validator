<?php

class Validator extends Prefab {
	use ValidationRules, FormatMessage, Helper;
	
	protected $data;
	protected $rules = [];
	protected $currentRule;
	protected $failedRules;
	protected $after = [];
	protected $messages = [];
	public $customMessages = [];

	protected $extensions = [];
	
	protected $numericRules = ['Numeric', 'Integer'];
	protected $sizeRules = ['Size', 'Between', 'Min', 'Max'];

	public function __construct(array $data = [], array $rules = [], array $messages = []) {
    	$this->data = $this->parseData($data);
    	$this->rules = $this->parseRules($rules);
		$this->customMessages = $messages;
    }

    public function validate(array $data = [], array $rules = [], array $messages = []) {
		$this->data = array_merge_recursive($this->data, $this->parseData($data));
		$this->rules = array_merge_recursive($this->rules, $this->parseRules($rules));
		$this->customMessages = $messages;
		
		foreach ($this->rules as $attribute => $rules) {
            $attribute = str_replace('\.', '->', $attribute);

            foreach ($rules as $rule) {
				$this->validateAttribute($attribute, $rule);
				
				if(isset($this->failedRules[$attribute])) {
					break;
				}
			}
		}
		
		foreach ($this->after as $after) {
            call_user_func($after);
        }
		
		return $this;
	}
	
	public function passed() {
		return (count($this->messages, COUNT_RECURSIVE) - count($this->messages)) <= 0 && empty($this->messages);
	}
	
	public function failed() {
        return $this->failedRules;
    }
	
	public function messages() {
        if(!$this->messages){
            $this->validate();
        }

        return $this->messages;
    }
	
	public function errors() {
        return $this->messages();
    }

    public function after($callback) {
        $this->after[] = function () use ($callback) {
            return call_user_func_array($callback, [$this]);
        };

        return $this;
    }

    public function addExtensions(array $extensions) {
        if ($extensions) {
            $keys = array_map('Base::instance()->snakecase', array_keys($extensions));
            $extensions = array_combine($keys, array_values($extensions));
        }

        $this->extensions = array_merge($this->extensions, $extensions);
    }

	public function addExtension($rule, $extension) {
        $this->extensions[$this->snake($rule)] = $extension;
    }
	
	public function parseData(array $data) {
		$newData = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->parseData($value);
            }

            if ($this->contains($key, '.')) {
                $newData[str_replace('.', '->', $key)] = $value;
            } else {
                $newData[$key] = $value;
            }
        }

        return $newData;
	}
	
	public function hasRule($attribute, $rules) {
        return ! is_null($this->getRule($attribute, $rules));
    }
	
	protected function parseRules(array $rules) {
		foreach ($rules as $key => $rule) {
			$rules[$key] = explode('|', $rule);
		}
		
		return $rules;
	}
	
	protected function validateAttribute($attribute, $rule) {
		$this->currentRule = $rule;

        list($rule, $parameters) = $this->makeRule($rule);
		
		if ($rule == '') {
            return;
        }
		
		$value = isset($this->data[$attribute]) ? $this->data[$attribute] : null;
		$method = "validate{$rule}";
		
		if (!$this->$method($attribute, $value, $parameters, $this)) {
            $this->addFailure($attribute, $rule, $parameters);
        }
	}
	
	protected function addFailure($attribute, $rule, $parameters) {
		$this->messages[$attribute] = $this->getMessage($attribute, $rule, $parameters);
        $this->failedRules[$attribute][$rule] = $parameters;
    }
	
	protected function makeRule($rules) {
		if (is_array($rules)) {
            $rules = [$this->studly(trim($rules[0])), array_slice($rules, 1)];
        } else {
			$parameters = [];

			if (strpos($rules, ':') !== false) {
				list($rules, $parameter) = explode(':', $rules, 2);

				if (strtolower($rules) == 'regex') {
					$parameters = [$parameter];
				}else{
					$parameters =  str_getcsv($parameter);
				}
			}

			$rules = [$this->studly(trim($rules)), $parameters];
        }

        switch ($rules[0]) {
            case 'Int':
                $rules[0] = 'Integer';
            case 'Bool':
                $rules[0] = 'Boolean';
            default:
                $rules[0] = $rules[0];
        }

        return $rules;
	}
	
	protected function getRule($attribute, $rules) {
        if (! array_key_exists($attribute, $this->rules)) {
            return;
        }

        $rules = (array) $rules;

        foreach ($this->rules[$attribute] as $rule) {
            list($rule, $parameters) = $this->makeRule($rule);

            if (in_array($rule, $rules)) {
                return [$rule, $parameters];
            }
        }
    }
	
	protected function getSize($attribute, $value) {
        $hasNumeric = $this->hasRule($attribute, $this->numericRules);

        if (is_numeric($value) && $hasNumeric) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof File) {
            return $value->getSize() / 1024;
        }

        return mb_strlen($value);
    }
	
	protected function requireParameterCount($count, $parameters, $rule) {
        if (count($parameters) < $count) {
            throw new InvalidArgumentException("Validation rule $rule requires at least $count parameters.");
        }
    }
	
	protected function callExtension($rule, $parameters) {
        $callback = $this->extensions[$rule];

        if (is_callable($callback)) {
            return call_user_func_array($callback, $parameters);
        }
    }
	
	public function __call($method, $parameters) {
        $rule = $this->snake(substr($method, 8));

        if (isset($this->extensions[$rule])) {
            return $this->callExtension($rule, $parameters);
        }

        throw new BadMethodCallException("Method [$method] does not exist.");
    }
	
	public function str_contains($haystack, $needles) {
		return $this->contains($haystack, $needles);
	}
}