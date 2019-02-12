# f3-validator

Easy to use Validation package for Fatfree-framework with using F3 built-in translations. You can also use it directly in the model.

**NOTE:** This package port some classes and ideas from [illuminate/validator](https://github.com/illuminate/validator).
**NOTE:** SAMPLE DICT/LANG FILE IS INCLUDED AS `en.php.sample`, use this for reference.

## Usage

- 1: Initializing, passing data and validate:

	`$validator = new Validator(
		array('name' => 'Anand Pilania', 'email' => 'abc@def.ge'),
		array('name' => 'required|min:3|max:60', 'email' => 'required|unique:Models\User|email')
	)->validate();`
	
	**NOTE:** You can also pass more data to the `validate` method.
	
	* Another method:
		
		`$validator = Validator::instance()->validate(
			array('name' => 'Anand Pilania', 'email' => 'abc@def.ge'),
			array('name' => 'required|min:3|max:60', 'email' => 'required|unique:NAMESPACED_MODEL_CLASS_OR_CONFIG_MODEL.PARAM`|email')
		);`
	
- 2: Get validation status (return -> true/false):

	`$validator->passed();`
	
- 3: If validation failed, retrieve the failed attribute with formatted messages/errors (return -> array):

	`$validator->errors();`
		

## Setup

- `composer require anandpilania/f3-validator`
  
## Rules

### Used without paramater
 * `boolean` OR `bool`, `string`, `integer` OR `int`, `array`, `numeric`, `alpha`, `alpha_num`, `alpha_dash`,
 * `url`, `nullable`, `json`, `required`, `confirmed`, `active_url`, `email`, `ip`, `ipv4`, `ipv6`, `filled`
 
### Used with parameters
 *  `min`, `max`, `size`, `between` - `min:6`, `max:255`, `size:3`, `between:1,3`
 *  `unique` - `unique:NAMESPACED_MODEL_CLASS_OR_CONFIG_MODEL`
 *  ...

## USING IN MODEL
- RECOMMENDED : Validate user input in controllers is the recommended way

### CONTROLLER
	`$validator = Validator::instance()->validate($f3->get('POST'), array(
		'field' => 'required|CHECK_PARAM' // EG: 'email' => 'required|email|unique:Models\User'
	));`
	
	`if(!$validator->passed()) {
		return/flash $validator->errors();
	}`
	
	
## USING IN MODEL
- You can also use this validator directly into your model:
FOR EX: Validatin on `beforesave` trigger: (This example is used with [ikkez/f3-cortex](https://github.com/ikkez/f3-cortex))

### Model
	`protected $validator;
	 public function __construct() {
	      parent::__construct();
	      $saveHandler = function() {
		       foreach($this->getFieldConfiguration() as $field => $conf) {
            	    if(isset($conf['validate'])) {
                	    $rules[$field] = $conf['validate'];
                	    $data[$field] = $this->get($field);
                	    if(Validator::instance()->str_contains($conf['validate'], 'confirmed')) {
            	    	   $confirmation = $field.'_confirmation';
                    	   $data[$field.'_confirmation'] = null !== $f3->get('POST.'.$confirmation)?$f3->get('POST.'.$confirmation):$f3->get('GET.'.$confirmation);
                	    }
            	    }
        	    }
        	    $this->validator = Validator::instance()->validate($data, $rules);
		        return $this->validator->passed();
    	   };
	   $this->beforesave($saveHandler);
	}`

### Controller
	`$model = new MODEL;
	 $model->copyFrom($f3->get('POST'), array_keys($model->fieldConf));
	 $mode->save();
	 
	 if($model->validator->passed()) {
	 	// NO ERRORS
	 }else{
	 	// VAIDATION FAILED
		$errors = $model->validator->errors();
	 }`
--
http://about.me/anandpilania

### USING UNIQUE VALIDATOR (2 WAYS)
 * Simply pass the NAMESPACED_MODEL_CLASS => `unique:Models\User`
 * Define in config as => 
	
	INI => `MODEL.USERS = Models\User` 
	HIVE => $f3->set('MODEL.USERS', 'Models\User');
	
	and use it as `unique:users`
