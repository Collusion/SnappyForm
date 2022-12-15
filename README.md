# SnappyForm
 Form processing made easy with PHP
 
 User defined callback functions for form elements<br>
 No need for complicated if/else structures<br>
 Supports optional inputs and array data<br>
 Automatic filling for <input> value attributes, <option> selected attributes etc. <br>
 Asynchronous value checks & form submit (configurable)
 
 Please see demo.php for complete usage instructions
 
 A form processed with SnappyForm can be tested here:
 https://www.pickmybra.in/snappy/demo.php
 
 A list of methods:
 
 # form_filter(string $submit_element)
 - Required only if multiple forms are to be processed.
 - Calling this before deploying settings or printing form values limits the effects on that particular form only. 

 # set_error_messages(string | array $msg)
 - takes a string or an associative array as parameter, sets general or element specific error messages
 - $msg = "This error message will be shown for all inputs";
 - $msg["element_name"] = "element specific error message";

 # set_rules(array $rules)
 - sets input filtering rules for different inputs
 - associative array formats: 
 - $rules["element_name_1"] = array("filter_function_1");
 - $rules["element_name_2"] = array("filter_function_2" => array("param1", "param2"));

 # set_async_submit(bool $value)
 - enable / disable asynchronous form submissions
 
 # set_async_check(bool $value)
 - enable / disable asynchronous individual input value checks
 
 # set_async_reset(bool $value)
 - enabled / disable form reset after successful asynchronous submit
 
 # set_callback_function(string $function_name, obj $instance = null)
 - calls $function_name after successful form submission.
 - If $instance is provided, method named $function_name will be called from the provided instance
 
 # set_loading_message(string $loading_message)
 - a loading message can be shown during the asynchronous input value checks
 - will be shown in the error message container, unless an input specific loader position is defined with the loader() method

 # set_default_values(array $values)
 - default values can be set for the form inputs with this method
 - associative array format: $values["element_name"] = "element default value";
 
 # process_form(string|array $element_name, string $form_method = "")
 - processes form data, is triggered by form element named $element_name
 - also accepts multiple element names in array format, if multiple forms are processed with the same SnappyForm instance
 - $form_method can be null/empty, "post" or "get"
 - returns NULL if no form submission was detected, 
 - false if input values failed to pass the defined rules
 - true if input values passed the rule check

 # resetform(void)
 - resets all form values to defaults (for example, after successful submission)

 # success(string $success_message)
 - returns a success message after successful form submission

 # failure(string $callback_failure_message)
 - returns an error, if callback function is defined and it returns a non true value
 
 # error(string $element_name)
 - returns an input value error set with set_error_messages(), if errornous value was provided and error message is defined
 
 # loader(string $element_name)
 - places the loading message, which will be shown during asynchronous input value checks (if set)
 
 # value(string $element_name, string $seek = "", string $return = "")
 - returns submitted value for the given input/element
 - if second parameter is defined and it matches the submitted value, third parameter is returned. returns empty string if values do not match.
 
 # print_async_handler(string $target_file = "")
 - prints javascript handler ( < script> ... < /script> ) for asynchronous value checks and form submission
 - takes $target_file as an optional parameter - required only if the form is printed and processed in different files
 - call this function only after the form has been printed, not before it!


 
 
