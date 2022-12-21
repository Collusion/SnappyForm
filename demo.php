<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("SnappyForm.php");

# let's create a new SnappyForm instance!
$SF = new SnappyForm();

# format error messages
$error_pre_html = "<span style='color:#ff0000;'>";
$error_post_html = "</span>";

# if you want to process multiple forms with the same SnappyForm instance
# you need to use the form_filter() method to specify which form is now being configured
# later on the same form_filter() method must be called prior outputting form values 
# with success(), failure(), error(), value() etc methods
#$SF->form_filter("my_submit_element_name");

# Set general error message for all elements 
$SF->set_error_messages("Your input was incorrect", $error_pre_html, $error_post_html);

# Set element specific error messages
$error_messages = array("age" 	=> "Age must be between 5 and 100",
						"email"	=> "A valid email address must be provided",
						"cities" => "Please select 1-2 cities",
						"friends" => "Please give a name with 5-40 chars and a valid email address",
						"options" => "Please choose at least one.",
						"terms" => "You must accept terms&conditions");
						
$SF->set_error_messages($error_messages);

/*
now, let's define how the different elements should be processed
these can be either native php functions, user defined php functions 
or methods built in into SnappyForm class
usage
---------------------------------------------------
$rules = array( "element_name_1" => "function_name"
				"element_name_2" => "function_name"
				...
				"element_name_X" => "function_name");
# multiple functions for single element
$rules = array("element_name" => array("function_name_1", "function_name_2"));
# multiple functions with parameters for single element
$rules = array("element_name" => array(	"function_name_1" => array("param1", "param2"), 
										"function_name_2",
										"function_name_3" => array("param1") 
										));
										
element naming convention
---------------------------------------------------
element_name	=> required, single value input
+element_name	=> optional element
				   defined functions will be executed only if element_value != "" 
element_name[]	=> required, multi value input (array)
+element_name[]	=> optional, multi value input (array)
				   defined functions will be executed if any array value is != ""
				   if all array values are empty strings, functions will be skipped
*/

				# let's call inbuilt function length 
				# name elements value must be between 3-15 chars in length
$rules = array("name" 	=> array("length" 		=> array(3, 15)),

				# email element must pass native php function filter_var($value, FILTER_VALIDATE_EMAIL)
				"email" => array("filter_var" 	=> FILTER_VALIDATE_EMAIL),	
				
				# age is an optional field, so we'll add a plus sign before it's name
				# but if user fills the element, the value must pass the defined functions
				# we'll call SnappyForn method is_intval without additional parameters
				# and inbuilt value_range function with 5(min) & 100(max) as parameters
				"+age"	=> array(	"is_intval",
									"value_range"	=> array(5, 100)),
				
				# gender is also optional field (hence the plus sign again)
				# we'll call SnappyForn method is_intval without additional parameters
				# and inbuilt value_range function with 1 + 2 as parameters	
				"+gender" => array(	"is_intval",
									"value_range"	=> array(1, 2)),
						
				# terms and conditions checkbox must return something
				# well call !empty(), which checks if non empty string is returned
				# if left unchecked, nothing will be returned and the check will fail				
				"terms" => array("!empty"),
				
				# options array consists of "ints" between 1-3, check them
				# at least 1 value is required					
				"options[]" => array("array_is_intval",
									"array_value_range"	=> array(1, 3),
									"array_count"		=> array(1, 3)),
				
				# cities[] originates from a multiple <select> element
				# hence will define here with brackets []
				# let's call inbuilt methods:
				# array_is_intval()	
				# array_count (min_count=1,max_count=2)
				# array_value_range(min=1, max=4)		
				"cities[]" => array("array_is_intval",
									"array_count"		=> array(1, 2),
									"array_value_range" => array(1,4)),
				
				# msg element's length must be between 5-100 chars 				
				"msg"	=> array("length" => array(5, 100)),
				
				
				# optional friend listing
				# lets use the inbuilt userCheck function for this	
				# userCheck can handle 2d arrays like this		
				"+friends[]"	=> array("userCheck"),
				
				
				# optional files input (TBD!)	
				# "+@files"	=> array("filetype" => array("jpg", "jpeg", "gif", "png")),

				);
				
# let's create a custom function for parsing data from "friends" input
# this function could also be added as a method into the SnappyForm class
# input: 2d array with deeper nodes being associative arrays
# the fname and email indexes will be checked for the deeper nodes
# fname must have appropiate length and email must be a valid email address
# output: true on success (all values correct), false on failure (any of the values incorrect)
function userCheck(array $data)
{
	foreach ( $data as $array ) 
	{
		if ( !isset($array["fname"]) || !is_string($array["fname"]) )	return false; # name must be string
		if ( !isset($array["email"]) || !is_string($array["email"]) )	return false; # email must be string
		
		$name = ( $array["fname"] != '' );
		$email = ( $array["email"] != '' );

		if ( $name != $email ) 													return false; # both must be set/not set 
		$name_len = mb_strlen($array["fname"]);									# get fnames length
		if ( $name && ($name_len < 5 || $name_len > 40) ) 						return false; # name must be valid length
		if ( $email && !filter_var($array["email"], FILTER_VALIDATE_EMAIL) ) 	return false; # email must be valid
	}
	
	return true;
}

# example of a user defined callback function
# which will be called with the submitted data as a parameter after the form has been submitted successfully
# this function MUST return true on success
function myCallbackFunction($data)
{
	# in async mode any output from callback function will be suppressed
	# but with synchronous submits output is visible 
	echo "This is output from the callback function"; 

	# send an email or save data into database
	# return true on success or false on failure

	return true;
}

# allow async form checking via inbuilt javascript handler
# the handler <script> must be printed later with the $SF->print_async_handler() method
# IMPORTANT: 	your script must not print anything before the $SF->process_form() method is called
# 				if you do not use a separate file for processing the form submits (defined by an optional parameter
# for asynchronous checking, the php handler is allowed to echo only json encoded data

# allow asynchronous form submitting (default false)
$SF->set_async_submit(true);

# allow asynchronous individual input value checking (on focusout events) (default false)
$SF->set_async_check(true);

# reset form values after successful async submit ? (default true)
$SF->set_async_reset(true);

# set_submit_loading_value() replaces submit button's value after clicking it & disables it temporarily
# value is reverted back to original value after a completed asynchronous submit
# this works also for synchronous submits, even with set_async_submit() set to false
$SF->set_submit_loading_value("Please wait...");

# set custom loading message to be shown during asynchronous value checks
# this message will be shown where the errors would be shown normally
#$SF->set_loading_message("<span style=\"color:#666;\">Loading...</span>");

# set form filtering rules
$SF->set_rules($rules);

# set callback function to be called upon successful form submission
# this can be either an user defined function or a method user has added into SnappyForm class
# THIS FUNCTION MUST RETURN A VALUE! true on success, false on failure
# otherwise a failure message will be shown ( created by failure() )
$SF->set_callback_function("myCallbackFunction");

# you can also call a predefined method of the provided (third party) class instance
#$SF->set_callback_function("method", $classInstance);

# set default values form the form
# IMPORTANT: ALL individual values (even the numeric ones) must be in STRING format!
# otherwise they WILL NOT WORK !				
$SF->set_default_values(array("name" 	=> "John Locke"));
$SF->set_default_values(array("cities" 	=> array("2","4")));
$SF->set_default_values(array("options" => array("2")));
$SF->set_default_values(array("msg"		=> "Hello Sir! How are you?"));
				
# catch form submission by submit element's name:
# like: <input type='submit' name='formsubmit' value='Submit' />
# first parameter is the submit's element name (string) or (array) if multiple forms/submit element's are present
# an optional second parameter can be defined to limit submit check to $_POST / $_GET only => post / get respectively
# returns true on success (form submitted + data ok)
# false on failure (form submitted, but incorrect values)
# null if no form submission detected
$success = $SF->process_form("formsubmit", "post");
#$success = $SF->process_form(array("registration", "login"), "post"); # process multiple forms

# check if form has been submitted
# if yes: let's past the rules to the process_fields() method
if ( $success )
{
	# yahoo, no errors ! 
	# now you are free to store / mail the $_GET / $_POST fields you've checked here
	# or use the set_callback_function() method to set a callback function that will be called automatically

	# reset form fields
	$SF->resetform(); 
}

?>

<!DOCTYPE html>
<!--[if (gte IE 8)|!(IE)]><!--><html lang="en"> <!--<![endif]-->
<head>
<title>SnappyForm demonstration</title>
</head>

<body>

<!-- if you want to process multiple forms with the same SnappyForm instance -->
<!-- you need to use the form_filter() method to specify which form is now being configured -->
<!-- later on the same form_filter() method must be called prior outputting form values -->
<!-- with success(), failure(), error(), value() etc methods -->
<!-- $SF->form_filter("my_submit_element_name"); -->

<!-- Prints a success message via the inbuilt success() method -->
<!-- message will NOT BE PRINT unless a successful submission is detected -->
<!-- this way the message can also be displayed with asynchronous form submissions -->
<?php echo $SF->success("<h3>Form was submitted successfully</h3>"); ?>

<!-- Prints a failure message via the inbuilt failure() method -->
<!-- message will NOT BE PRINT unless a successful form submission is detected, followed by a failed callback function  -->
<!-- this way the message can also be displayed with asynchronous form submissions -->
<?php echo $SF->failure("<h3 style='color:#cc0000;'>Sorry, we encountered an internal error, your data was not saved</h3>"); ?>

<p>
<form method="post">
    <p>
        <label for='name'>Your name *</label><br>
        <!-- value() returns the POST/GET value of the given element, if it's defined -->
        <!-- value is returned via htmlspecialchars(value, ENT_QUOTES), so it SHOULD be safe to display -->
        <input name='name' id='name' type='text' value='<?php echo $SF->value("name"); ?>'/> <?php echo $SF->loader("name"); ?>
        <br>
        <?php echo $SF->error("name"); ?>
    </p>
    <p>
        <label for='email'>Your email address *</label><br>
        <input name='email' id='email' type='text' value='<?php echo $SF->value("email"); ?>'/>
        <br>
        <?php echo $SF->error("email"); ?>
    </p>
    <p>
        <label for='age'>Your age (optional)</label><br>
        <input name='age' id='age' type='text' value='<?php echo $SF->value("age"); ?>'/>
        <br>
        <?php echo $SF->error("age"); ?>
    </p>
	<p>
        <label for='gender'>Gender (optional)</label><br>
        <!-- value() takes up to 3 parameters: 'element_name', 'seek' & 'return' -->
        <!-- if 'seek' is given and it matches element's value, 'return' is returned, otherwise an empty string '' will be returned -->
        <input name='gender' id='gender' type='radio' value='1' <?php echo $SF->value("gender", "1", "checked"); ?>/> Male <br>
        <input name='gender' id='gender' type='radio' value='2' <?php echo $SF->value("gender", "2", "checked"); ?>/> Female <br>
        <?php echo $SF->error("gender"); ?>
    </p>
    <p>
        <label for=''>Options *</label><br>
        <!-- value() takes up to 3 parameters: 'element_name', 'seek' & 'return' -->
        <!-- if 'seek' is given and it matches element's value, 'return' is returned, otherwise an empty string '' will be returned -->
        <input name='options[]' type='checkbox' value='1' <?php echo $SF->value("options", "1", "checked"); ?>/> Drinks <br>
        <input name='options[]' type='checkbox' value='2' <?php echo $SF->value("options", "2", "checked"); ?>/> Dinner <br>
        <input name='options[]' type='checkbox' value='3' <?php echo $SF->value("options", "3", "checked"); ?>/> Dessert <br>
        <?php echo $SF->error("options"); ?>
    </p>
    <p>
        Terms and conditions *
        <!-- value() takes up to 3 parameters: 'element_name', 'seek' & 'return' -->
        <!-- if 'seek' is given and it matches element's value, 'return' is returned, otherwise an empty string '' will be returned -->
        <input name='terms' id='terms' type='checkbox' value='yes' <?php echo $SF->value("terms", "yes", "checked"); ?>/> <label for='terms' style='color:#666;'>I accept terms and conditions </label>
        <br>
        <?php echo $SF->error("terms"); ?>
    </p>
    <p>
        <label for='cities'>Your favorite cities (min 1, max 2) *</label><br>
        <select name='cities[]' id='cities' multiple>
        	<!-- value() takes up to 3 parameters: 'element_name', 'seek' & 'return' -->
            <!-- if 'seek' is given and it matches element's value, 'return' is returned, otherwise an empty string '' will be returned -->
            <!-- with array input value("element_name", "seek", "return") does an array search instead of direct comparison -->
        	<option value='1' <?php echo $SF->value("cities", "1", "selected"); ?>>Helsinki</option>
            <option value='2' <?php echo $SF->value("cities", "2", "selected"); ?>>Copenhagen</option>
            <option value='3' <?php echo $SF->value("cities", "3", "selected"); ?>>Oslo</option>
            <option value='4' <?php echo $SF->value("cities", "4", "selected"); ?>>Stockholm</option>
        </select>
         <?php echo $SF->loader("cities"); ?>
        <br>
        <?php echo $SF->error("cities"); ?>
    </p>
    <p>
        <label for='msg'>Your message *</label><br>
        <textarea name='msg' id='msg'><?php echo $SF->value("msg"); ?></textarea>
        <br>
        <?php echo $SF->error("msg"); ?>
    </p>
    <p>
        <label for='friends1'>Send a newsletter to your friends (optional)</label><br>
        <!-- with array input calls on value() will return a single string value from the input array -->
        <!-- calls will increment the array's internal pointer by one, so a consecutive will return the next value and so on  -->
        <input name='friends[0][fname]' value='<?php echo $SF->value("friends"); ?>' placeholder='Name #1' id='friends1' type='text' />&nbsp;
        <input name='friends[0][email]' value='<?php echo $SF->value("friends"); ?>' placeholder='Email #1' id='friends2' type='text' /><br>
        <input name='friends[1][fname]' value='<?php echo $SF->value("friends"); ?>' placeholder='Name #2' id='friends3' type='text' />&nbsp;
        <input name='friends[1][email]' value='<?php echo $SF->value("friends"); ?>' placeholder='Email #2' id='friends4' type='text' /><br>
        <input name='friends[2][fname]' value='<?php echo $SF->value("friends"); ?>' placeholder='Name #3' id='friends5' type='text' />&nbsp;
        <input name='friends[2][email]' value='<?php echo $SF->value("friends"); ?>' placeholder='Email #3' id='friends6' type='text' /><br>
        <?php echo $SF->error("friends"); ?>
    </p>
    <p>
    	<!-- calling an array input with brackets, friends[], will return the original data as it is -->
        <!-- in this case it will be returned in array format -->
        <!-- NOTICE: the data is not safe for printing as it is, please use htmlspecialchars for output !!! -->
    	<label for='raw'>Raw data from friends input (the one above)</label><br>
        <textarea name='raw' id='raw' cols='35' rows='15' disabled><?php print_r($SF->value("friends[]")); ?></textarea>
    </p>
	<p>
    	<input name='formsubmit' type='submit' value='Submit form' />
    </p>
</form>

<!-- print handler for asynchronous value checks -->
<!-- Must be called AFTER all the forms processed by SnappyForm ! -->
<!-- parameters: -->
<!-- 1. async handler php file (OPTIONAL, default is the file handler is being called from) -->

<?php $SF->print_async_handler(); ?>

</body>
</html>