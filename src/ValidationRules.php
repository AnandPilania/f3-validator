<?php

trait ValidationRules {
	public function validateAccepted($attribute, $value) {
        $acceptable = ['yes', 'on', '1', 1, true, 'true'];
        return $this->validateRequired($attribute, $value) && in_array($value, $acceptable, true);
    }
	
	public function validateActiveUrl($attribute, $value) {
        if (! is_string($value)) {
            return false;
        }

        if ($url = parse_url($value, PHP_URL_HOST)) {
            try {
                return count(dns_get_record($url, DNS_A | DNS_AAAA)) > 0;
            } catch (Exception $e) {
                return false;
            }
        }

        return false;
    }
	
	public function validateRequired($attribute, $value) {
		if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof File) {
            return (string) $value->getPath() !== '';
        }

        return true;
	}
	
	public function validateEmail($attribute, $value) {
		return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
	}
	
	public function validateMax($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'max');

        if ($value instanceof File && ! $value->isValid()) {
            return false;
        }

        return $this->getSize($attribute, $value) <= $parameters[0];
    }
	
	public function validateMin($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'min');
        return $this->getSize($attribute, $value) >= $parameters[0];
    }
	
	public function validateBetween($attribute, $value, $parameters) {
        $this->requireParameterCount(2, $parameters, 'between');
        $size = $this->getSize($attribute, $value);

        return $size >= $parameters[0] && $size <= $parameters[1];
    }
	
	public function validateBoolean($attribute, $value) {
        $acceptable = [true, false, 0, 1, '0', '1'];
        return in_array($value, $acceptable, true);
    }
	
	public function validateConfirmed($attribute, $value) {
        return $this->validateSame($attribute, $value, [$attribute.'_confirmation']);
    }
	
	public function validateSame($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'same');
        $other = isset($this->data[$parameters[0]]) ? $this->data[$parameters[0]] : null;

        return $value === $other;
    }
	
	public function validateSize($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'size');
        return $this->getSize($attribute, $value) == $parameters[0];
    }
	
	public function validateString($attribute, $value) {
        return is_string($value);
    }
	
	public function validateArray($attribute, $value) {
        return is_array($value);
    }
	
	public function validateInteger($attribute, $value) {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
	
	public function validateNumeric($attribute, $value) {
        return is_numeric($value);
    }
	
	public function validateNullable() {
        return true;
    }
	
	public function validateRegex($attribute, $value, $parameters) {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        $this->requireParameterCount(1, $parameters, 'regex');

        return preg_match($parameters[0], $value) > 0;
    }
	
	public function validateUnique($attribute, $value, $parameters) {
		$this->requireParameterCount(1, $parameters, 'unique');
		if(class_exists($parameters[0])) {
			$class = $parameters[0];
		}else if($cClass = Base::instance()->get('MODEL.'.strtoupper($parameters[0]))) {
			$class = $cClass;
		}
        $model = new $class;
		$model->load(array($attribute.' =?', $value));
        return false !== $model->dry();
	}
	
	public function validateAlpha($attribute, $value) {
        return is_string($value) && preg_match('/^[\pL\pM]+$/u', $value);
    }
	
	public function validateAlphaDash($attribute, $value) {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN_-]+$/u', $value) > 0;
    }

    public function validateAlphaNum($attribute, $value) {
        if (! is_string($value) && ! is_numeric($value)) {
            return false;
        }

        return preg_match('/^[\pL\pM\pN]+$/u', $value) > 0;
    }
	
	public function validateDifferent($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'different');

        foreach ($parameters as $parameter) {
            $other = isset($this->data[$parameter])?$this->data[$parameter]:null;

            if (is_null($other) || $value === $other) {
                return false;
            }
        }

        return true;
    }
	
	public function validateDigits($attribute, $value, $parameters) {
        $this->requireParameterCount(1, $parameters, 'digits');

        return ! preg_match('/[^0-9]/', $value) && strlen((string) $value) == $parameters[0];
    }

    public function validateDigitsBetween($attribute, $value, $parameters) {
        $this->requireParameterCount(2, $parameters, 'digits_between');

        $length = strlen((string) $value);

        return ! preg_match('/[^0-9]/', $value) && $length >= $parameters[0] && $length <= $parameters[1];
    }
	
	public function validateFilled($attribute, $value) {
        if (isset($this->data[$parameter])) {
            return $this->validateRequired($attribute, $value);
        }

        return true;
    }
	
	public function validateIn($attribute, $value, $parameters) {
        if (is_array($value) && $this->hasRule($attribute, 'Array')) {
            foreach ($value as $element) {
                if (is_array($element)) {
                    return false;
                }
            }

            return count(array_diff($value, $parameters)) == 0;
        }

        return ! is_array($value) && in_array((string) $value, $parameters);
    }
	
	public function validateIp($attribute, $value) {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    public function validateIpv4($attribute, $value) {
        return Audit::instance()->ipv4($value);
    }

    public function validateIpv6($attribute, $value) {
        return Audit::instance()->ipv6($value);
    }
	
	public function validateUrl($attribute, $value) {
        return Audit::instance()->url($value);
    }

	public function validateCard($attribute, $value) {
        return Audit::instance()->card($value);
    }
	
	public function validateEntropy($attribute, $value) {
        return Audit::instance()->entropy($value);
    }
	
	public function validatePublicIp($attribute, $value) {
        return Audit::instance()->ispublic($value);
    }
	
	public function validatePrivateIp($attribute, $value) {
        return Audit::instance()->isprivate($value);
    }
	
	public function validateReservedIp($attribute, $value) {
        return Audit::instance()->isreserved($value);
    }
	
    public function validateJson($attribute, $value) {
        if (! is_scalar($value) && ! method_exists($value, '__toString')) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
	
	public function validateNotIn($attribute, $value, $parameters) {
        return ! $this->validateIn($attribute, $value, $parameters);
    }
}