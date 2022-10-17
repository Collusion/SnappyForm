<?php

class SnappyForm
{
	private $element_errors;
	private $element_values;
	private $error_messages;
	private $flattened_arrays;
	private $rules;
	private $data;
	private $async_allowed;
	private $loading_msg;
	private $error_message_pre;
	private $error_message_post;
	private $general_error_message;

	public function __construct()
	{
		$this->element_errors = array();	# element specific errors 
		$this->element_values = array();	# element specific values 
		$this->error_messages = array();	# element specific errors 
		$this->flattened_arrays = array(); 	# contains flattened array data (multidim => 1d)
		$this->rules			= array();	# contains filtering rules for the form to be processed
		$this->data						= array();	
		$this->async_allowed			= false;
		$this->loading_msg				= "";
		$this->error_message_pre 		= "";
		$this->error_message_post 		= "";
		$this->general_error_message 	= "Error: incorrect value";
	}
	
	/*
	In-built helper functions for checking element values
	You may need to define your own functions as well! 
	*/
	
	/* for checking inputs for exact value, like checkboxes etc */
	private function value_is($value, $match_value)
	{
		return ( $value == $match_value );
	}

	# checck if the given value is an "integer"
	# as in "10", "0" etc. and not "10.1" nor "0.1"
	# function does not test variable's actual type but it's contents
	private function is_intval($val)
	{
		return ( is_numeric($val) && is_int(0+$val) );
	}

	# since language constructs like "empty()" cannot be called via 
	# call_user_func_array, we define similar function here
	private function emptyval($value)
	{
		return empty($value);
	}
	
	# checks if given $value is between given $min $max values
	private function value_range($value, $min = 0, $max = PHP_INT_MAX)
	{
		return ( $value >= $min && $value <= $max ); 
	}
	
	# check if string lenght is between $min & $max values
	private function length($str, $min_len = 0, $max_len = 2147483648)
	{
		$len = mb_strlen($str);
		return ( $len >= $min_len && $len <= $max_len );
	}
	
	# validates given (first) name
	private function validate_name($str)
	{
		return ctype_alpha(str_replace(array(" ", "-", "."), "", $str));
	}
	
	# counts how many elements 1d array has
	# returns true of no of elements is within $min $max values
	# false otherwise
	private function array_count(array $arr, $min, $max)
	{
		$count = count($arr);
		return ( $count >= $min && $count <= $max );
	}
	
	# checks if 1d array values are between min|max params
	private function array_value_range(array $arr, $min, $max)
	{
		return ( min($arr) >= $min && max($arr) <= $max );
	}
	
	# checks if all values of 1d array are numeric
	private function array_is_numeric(array $arr)
	{
		foreach ( $arr as $val ) 
		{
			if ( !is_numeric($val) ) return false;
		}
		return true;
	}
	
	# checks if all values of 1d array are "integers"
	# see "is_intval"
	private function array_is_intval(array $arr)
	{
		foreach ( $arr as $val ) 
		{
			if ( !is_numeric($val) || !is_int(0+$val) ) return false;
		}
		return true;
	}
	
	# flattens a multidimensional array into an 1d array
	# input params: 
	# $array => the array to be flattened
	# $maxdepth => how deep the recursion / flattening is allowed to go (default: 10)
	# $accumulator => internal use only 
	# output:
	# flattened array (up to $maxdepth nodes)
	private function flattenArray(array $array, $maxdepth = 10, array &$accumulator = array())
	{
		foreach ( $array as $key => $value )
		{
			if ( !is_array($value) ) 
			{
				$accumulator[] = $value;
			}
			else if ( $maxdepth ) 
			{
				# we must go deeper...
				$this->flattenArray($value, --$maxdepth, $accumulator);
			}
		}
		
		return $accumulator;
	}
	
	/* FOR INTERNAL USE ONLY 
	returns single (string) value from the given element
	data can be in string format, when it's returned as is
	or in array format, when array will be fully iterated and first value in string format will be returned
	any subsequent calls on array data will return next (string) value from array, until values run out
	*/
	private function returnSingleValue($element_name)
	{
		if ( is_array($this->element_values[$element_name]) )
		{
			if ( !isset($this->flattened_arrays[$element_name]) ) 
			{
				# flatten the array into 1d (do it only once)
				$this->flattened_arrays[$element_name] = $this->flattenArray($this->element_values[$element_name]);
			}
			# create a reference for a cleaner code
			$arr = &$this->flattened_arrays[$element_name];
			$tmp = current($arr); # get current value from the array
			if ( $tmp === false ) return ''; # if the array already ended, return an empty string
			next($arr); # otherwise increment array pointer
			return $tmp;
		}
		else
		{
			return $this->element_values[$element_name];
		}
	}

	/* PUBLIC INTERFACING FUNCTIONS */
	
	/*
	sets custom error messages for given elements, shown via parser->error("element") call
	input params: 
		$e_data string or associative array
		string input will set general error message which will be dispayed for all incorrect elements 
		array input (arr["element_name"] => "error_message" will set element specific error messages
		which will be shown instead of the general error message
	$html_pre (string) this string will be added before the error message 
	$html_post (string) this string will be added after the error message
	*/
	public function set_error_messages($e_data, $html_pre = '', $html_post = '')
	{
		if ( is_array($e_data) ) 
		{
			foreach ( $e_data as $e_name => $e_msg ) 
			{
				# remove []+ chars from element_name
				$e_name = str_replace(array("[", "]", "+"), "", $e_name);
				
				if ( is_string($e_msg) ) $this->error_messages[$e_name] = $e_msg;
			}
		}
		else if ( is_string($e_data) )
		{
			$this->general_error_message = $e_data;
		}
		
		# set error pre/post html
		if ( $html_pre != '' ) $this->error_message_pre = $html_pre;
		if ( $html_post != '' ) $this->error_message_post = $html_post;
	}
	
	/* checks if given element has error messages defined
		if it has, a predefined error message will be returned
		otherwise an empty string will be returned	*/
	public function error($element_name)
	{
		# show error message by default?
		$visibility = ( empty($this->element_errors[$element_name]) ) ? 'display:none;' : '';
		
		$container_pre = "<span id='snappy_$element_name' style='$visibility'>";
		$container_post = "</span>";

		$emsg = ( !empty($this->error_messages[$element_name]) ) ? $this->error_messages[$element_name] : $this->general_error_message;
		return $container_pre . $this->error_message_pre . $emsg . $this->error_message_post . $container_post;
	}
	
	/* return value(s) from the processed form element
	input params: 
		(string) $element_name 	=> "element" OR "element[]"
		(string) $find 			=> NULL(default)
		(string) $replace 		=> 'checked'(default)
	output:
		(string) or (array)
		for (string) output provide $element_name like "element" 
			function always returns single (string) value
			for (array) data subsequent calls will return next (string) value
			from said array, until array runs out of values
			for (string) data all subsequent calls return the same value
		for (array)|(string) output provide $element_name like "element[]" 
			function returns all values for the given element
			data will be in the same format as submitted from the form
			if an array was submitted, an array will be returned
			if a string was submitted, a string will be returned
		if $seek input param is defined, output will be compared 
			against it => if they match, the given $return input parameter
			will be returned instead. If no match => empty string
	*/
	public function value($element_name, $seek = NULL, $return = 'checked')
	{
		$array_mode = 0;
		$element_name = str_replace("[]", "", $element_name, $array_mode);
		
		if ( isset($this->element_values[$element_name]) ) 
		{
			if ( $array_mode )
			{
				# "element[]" => user wants all values, return the original provided data
				return $this->element_values[$element_name];
			}
			
			# user wants a single value only
			$value = $this->returnSingleValue($element_name);

			# if $find param is defined
			if ( $seek ) 
			{
				# if the original data was in array format
				# do an array search 
				if ( isset($this->flattened_arrays[$element_name]) && $value != $seek ) 
				{
					$value = '';
					if ( in_array($seek, $this->flattened_arrays[$element_name], true) ) 
					{
						$value = $return;
					}
				}
				# original data was string => do an exact match test
				else
				{
					$value = ( $seek == $value ) ? $return : '';
				}
			}

			# return element value 
			return htmlspecialchars($value, ENT_QUOTES);
		}
		
		return '';
	}
	
	# prints an html element containing user defined loading message, hidden by default
	# this allows a customized positioning for the loader, instead of 
	# the in-place replacement of the error message
	# input:  element_name: form element name attribute
	# output: (string) container containing the custom loading message (if defined)
	#		  empty string otherwise
	public function loader($element_name)
	{
		$element_name = str_replace("[]", "", $element_name);
		return ( $this->loading_msg != "" ) ? "<span style='display:none;' id='snappy_loading_".$element_name."'>".$this->loading_msg."</span>" : '';
	}

	# prints javascript handler <script>...</ script>
	# for asynchronous value checking
	# also prints a global variable for holding loading message
	public function print_async_handler($form_id, $target_file = "")
	{
		if ( !$this->async_allowed )return '';
		if ( empty($form_id) ) 		return '';
		if ( empty($target_file) ) 	$target_file = basename($_SERVER['SCRIPT_NAME']);
		$lmsg = "var lm='".str_replace("'", "\'", $this->loading_msg)."'";

		echo	"<script>$lmsg\n"
				. str_replace(	array("myform", "demo.php"), 
								array($form_id, $target_file), 
								file_get_contents("handler.js")) .
				"</script>";
	}
	
	# sets an optional loading message, when async value check is in progress
	# will be shown where the error message would be shown
	public function set_loading_message($msg)
	{
		if ( is_string($msg) ) $this->loading_msg = $msg;
	}
	
	/* 
	set form input processing rules
	input: 
		an associative array: 
		rules[element_name] => array(	"filter_fn_1", 
										"filter_fn_2" => array("param_1", "param_2")
									)
	output: null
	*/
	public function set_rules(array $rules)
	{
		$this->rules = $rules;
	}
	
	# allow asynchronous input value checking
	# input: true|false
	public function set_async_mode($value)
	{
		$this->async_allowed = ( !empty($value) );
	}

	# resets internal variables 
	public function resetform()
	{
		$this->element_errors = array();
		$this->element_values = array();
		$this->error_messages = array();
	}
	
	/*
	process form data according to the filtering rules set by set_rules()
	input params:
	(string) submit_element 
		=> name attribute of your form's submit button
		=> <input type='submit' name='my_element' value='submit '/>
	(string) type (optional)
		=> 'post' for $_POST data / post method
		=> 'get' for $_GET data / get method
	output:
		null on:
		=> no rules defined, submit element not defined or incorrect
		=> no data available (via get/post)
		=> in asynchronous operation mode, $this->data["snappy_async_mode"] is set
		   this functions echoes "1" or "0"
		false on:
		=> form data does no pass provided rules
		true on
		=> form data passes provided rules
	*/
	public function process_form($submit_element, $type = false)
	{	
		if ( empty($this->rules) )	
		{
			trigger_error("Form processing rules not set", E_USER_WARNING);
			return NULL;
		}
		
		if ( !is_string($submit_element) ) 
		{
			trigger_error("Form submit element must be a string", E_USER_WARNING);
			return NULL;
		}
		
		if ( is_string($type) ) $type = strtolower($type);

		# if type is empty, check both GET + POST
		if ( isset($_POST) && ($type == 'post' || !$type ) ) 	$this->data = &$_POST;
		else if ( isset($_GET) && ($type == 'get' || !$type ) ) $this->data = &$_POST;
		else return NULL;

		# if we are in async mode 
		if ( isset($this->data["snappy_async_mode"]) ) 
		{
			if ( !$this->async_allowed ) exit();
			
			if ( $this->data["snappy_async_mode"] != "1" )
			{
				# this key contains an element that has no data (e.g. an unchecked checkbox)
				# check if it can be found from the defined rules with the plus sign (optional element) 
				# => rules["+element_name"] = array(rules), return 1 if yes, 0 otherwise
				exit(( isset($this->rules["+".$this->data["snappy_async_mode"]]) ) ? "1" : "0");
			}
			
			# check only the provided form elements and nothing else ! 
			# delete non-relevant rules
			foreach ( $this->rules as $elem => $elem_data ) 
			{
				$bare_elem = str_replace(array("[]", "+"), "", $elem);
				if ( !isset($this->data[$bare_elem]) ) 
				unset($this->rules[$elem]);
			}

			# instead of returning the result echo "1" or "0" and exit
			exit(( $this->checkData() ) ? "1" : "0");
		}
		
		return ( isset($this->data[$submit_element]) ) ? $this->checkData() : NULL ;
	}
	
	/*
	matches defined rules against form data 
	returns false (if incorrect function names/parameters)
	returns true if all functions return !empty() value
	*/
	private function checkData()
	{
		foreach ( $this->rules as $field => $functions ) 
		{
			# by default, all provided elements are required
			$optional_value = false;
			
			# check if element name hints array type: element_name[]
			$bracket_count = 0;
			$field = str_replace("[]", "", $field, $bracket_count);
			$array_element = ( $bracket_count > 0 );
			
			# if this element value is deemed optional
			if ( $field[0] === '+' ) 
			{
				$field = substr($field, 1);
				$optional_value = true;
			}
			
			# if field cannot be found from GET/POST values or if it's an empty string
			if ( !isset($this->data[$field]) || $this->data[$field] === '' ) 
			{
				# mark this field as incorrect
				if ( !$optional_value ) $this->element_errors[$field] = true;

				continue; # always skip empty optional values
			}

			# if given element type and actual value do not match
			if ( $array_element !== is_array($this->data[$field]) ) 
			{
				$this->element_errors[$field] = true;
				continue;
			}
		
			# always store the provided element value
			$this->element_values[$field] = $this->data[$field];
			
			# if the provided data is an array and it's deemed optional
			# if all array entries are empty strings, skip function execution
			if ( $array_element && $optional_value )
			{
				# convert multidimensional arrays into 1d
				$this->flattened_arrays[$field] = $this->flattenArray($this->data[$field]);
				
				$flag = 0;
				foreach ( $this->flattened_arrays[$field] as $item ) 
				{
					if ( $item !== "" )
					{
						 $flag = 1;
						 break;
					}
				}
				
				if ( !$flag ) continue;
			}
			
			if ( !is_array($functions) ) 
			{
				trigger_error("Incorrect rule format, function array expected", E_USER_WARNING);
				return null;
			}
				
			foreach ( $functions as $f1 => $f2 ) 
			{
				# the function to be called
				$call_function = $f2;
				# first parameter passed to the callable function will be the $_POST/$_GET value
				$call_parameters = array($this->data[$field]);
				# provided functions are to return "true" by empty() 
				$wanted_return_value = true;
				
				# [0] => "function_name" or ["function_name"] => array(params) 
				if ( !is_numeric($f1) ) 
				{
					$call_function = $f1;

					if ( is_array($f2) ) 
					{
						$call_parameters = array_merge($call_parameters, $f2);
					}
					else
					{
						$call_parameters[] = $f2;
					}
				}
				
				if ( $call_function[0] === "!" ) 
				{
					# defined function needs to return false ! 
					$call_function = substr($call_function, 1);
					$wanted_return_value = false;
				}

				# if empty() language construct is defined as a function
				# use inbuilt emptyval() function instead since empty() cannot
				#  be called via call_user_func_array
				if ( $call_function === 'empty' ) $call_function = 'emptyval';

				# try calling this class' method first
				if ( method_exists($this, $call_function) ) 
				{
					$call_function = array($this, $call_function);
				}
				else if ( !function_exists($call_function) ) 
				{
					trigger_error("Unknown function: {$call_function}()", E_USER_WARNING);
					# stated method unavailable
					$this->element_errors[$field] = true;
					continue;
				}
				
				# call the defined function with given parameters (call_params[0] is always the field value)
				$res = call_user_func_array($call_function, $call_parameters);
				
				# function output must evaluate as true by !empty(), otherwise it's an error
				if ( empty($res) === $wanted_return_value ) 
				{
					$this->element_errors[$field] = true;
				}
			}
		}

		# if there are no errors, form values are considered to be OK!
		return empty($this->element_errors);
	}		
}

?>