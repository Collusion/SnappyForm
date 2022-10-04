<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once("SnappyForm.php");

# let's create a new SnappyForm instance!
$SF = new SnappyForm();

# format error messages
$error_pre_html = "<br><span style='color:#ff0000;'>";
$error_post_html = "</span>";

# Set general error message for all elements 
$SF->set_error_messages("Your input was incorrect", $error_pre_html, $error_post_html);

# Set element specific error messages
$error_messages = array("age" 	=> "Age must be between 5 and 100",
						"email"	=> "A valid email address must be provided",
						"cities" => "Please select 1-2 cities",
						"friends" => "Please give a name with 5-40 chars and a valid email address");
						
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
				# and inbuilt value_range function with 5 + 100 as parameters
				"+age"	=> array(	"is_intval",
									"value_range"	=> array(5, 100)),
				
				# gender is also optional field (hence the plus sign again)
				# we'll call SnappyForn method is_intval without additional parameters
				# and inbuilt value_range function with 1 + 2 as parameters	
				"+gender" => array(	"is_intval",
									"value_range"	=> array(1, 2)),
				
				# cities[] originates from a multiple <select> element
				# hence will define here with brackets []
				# let's call inbuilt methods:
				# array_is_intval()	
				# array_count (min_count=1,max_count=2)
				# array_value_range(min=1, max=4)		
				"cities[]" => array("array_is_intval",
									"array_count"	=> array(1, 2),
									"array_value_range" => array(1,4)),
				
				# msg element's length must be between 5-100 chars 				
				"msg"	=> array("length" => array(5, 100)),
				
				
				# optional friend listing
				# lets use the inbuilt userCheck function for this	
				# userCheck can handle 2d arrays like this		
				"+friends[]"	=> array("userCheck"),
				
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
	
				
$success = "";

# check if form has been submitted
# if yes: let's past the rules to the process_fields() method
if ( isset($_POST["formsubmit"]) && $SF->process_fields("post", $rules) )
{
	# yahoo, no errors ! 
	# now you are free to store / mail the $_POST fields you've checked
	
	# define a general success message
	$success = "<h3>Form was submitted successfully</h3>";
	
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
<?php 

echo $success; 

?>
<p>
<form method="post">
    <p>
        <label for='name'>Your name *</label><br>
        <!-- value() returns the POST/GET value of the given element, if it's defined -->
        <!-- value is returned via htmlspecialchars(value, ENT_QUOTES), so it SHOULD be safe to display -->
        <input name='name' id='name' type='text' value='<?php echo $SF->value("name"); ?>'/>
        <?php echo $SF->error("name"); ?>
    </p>
    <p>
        <label for='email'>Your email address *</label><br>
        <input name='email' id='email' type='text' value='<?php echo $SF->value("email"); ?>'/>
        <?php echo $SF->error("email"); ?>
    </p>
    <p>
        <label for='age'>Your age (optional)</label><br>
        <input name='age' id='age' type='text' value='<?php echo $SF->value("age"); ?>'/>
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
        <?php echo $SF->error("cities"); ?>
    </p>
    <p>
        <label for='msg'>Your message *</label><br>
        <textarea name='msg' id='msg'><?php echo $SF->value("msg"); ?></textarea>
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
</body>
</html>