# SnappyForm
 Form processing made easy with PHP
 
 Used defined callback functions for form elements
 No need for complicated if/else structures
 Supports optional inputs and array data
 Automatic filling for <input> value attributes, <option> selected attributes etc. 
 
 Example:
 
 <label for='myinput'>Input value:</label><br>
 <input name='myinput' id='myinput' value='<?php echo $SF->value("myinput") ?>'><br>
 <?php echo $SF->error("myinput") ?>'>
 
 Please see demo.php for usage instructions
 
 A form processed with SnappyForm can be tested here:
 https://www.pickmybra.in/snappy/demo.php
 

