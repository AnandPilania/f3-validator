<?php

trait FormatMessage {	
	protected function getMessage($attribute, $rule, $parameters) {
		$inlineMessage = $this->getFromLocalArray($attribute, $lowerRule = $this->snake($rule));
		
		if (! is_null($inlineMessage)) {
            return $inlineMessage;
        }
		
		$f3 = Base::instance();
		$key = "error.validations.{$lowerRule}";
		$message = $f3->get($key);
		$replace = $this->prepend($parameters, $attribute);
		
		if(!is_null($message)) {
			if(is_array($message)) {
				$type = $this->getAttributeType($attribute);
				if(isset($message[$type])) {
					$message = $f3->get($key.'.'.$type, $replace);
				}else{
					$message = $f3->get('error.validations.required', $replace);
				}
			}else{
				$message = $f3->get($key, $replace);
			}
		}
		
		return $f3->format($message, [$attribute, (isset($parameters[0])?$parameters[0]:'')]);
	}
	
	protected function getFromLocalArray($attribute, $lowerRule, $source = null) {
        $source = $source ?: $this->customMessages;
		
		$keys = ["{$attribute}.{$lowerRule}", $lowerRule];

        foreach ($keys as $key) {
            foreach (array_keys($source) as $sourceKey) {
                if ($this->is($sourceKey, $key)) {
                    return $source[$sourceKey];
                }
            }
        }
    }
	
	protected function getAttributeType($attribute) {
        if ($this->hasRule($attribute, $this->numericRules)) {
            return 'numeric';
        } elseif ($this->hasRule($attribute, ['Array'])) {
            return 'array';
        } elseif ((isset($this->data[$attribute])?$this->data[$attribute]:null) instanceof File) {
            return 'file';
        }

        return 'string';
    }
}