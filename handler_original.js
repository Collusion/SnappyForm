var er_tmp;
var form;
var sbmit = document.querySelector("[name='myform'][type='submit']");
if ( sbmit != null ) form = sbmit.form;

// check if given element name attr refers to an array input
// as in it has equal number if '[' and ']'
function arrayName(n)
{
	var l = (n.match(/\[/g) || []).length;
	var r = (n.match(/\]/g) || []).length;
	return (l && l === r ? l : 0);
}

// returns given elements value property, if
// element is not a checkbox/radiobut or if it's an unchecked checkbox/radiobut 
function getValue(e)
{
	var t = e.getAttribute('type');
	return ( (t === 'checkbox' || t === 'radio') && !e.checked ? null : encodeURIComponent(e.value) );
}

// takes element id and state (int) as parameter
// sets element visible (display "") if id is found and pass == 0
function setVisibility(id, s)
{
	var e = document.getElementById(prefix+id);
	if ( e != null ) e.style.display = ( s ? "none" : "");
}

// get's given elements all values and send them to
function checkElem(e)
{
	if ( 	form == null || 
			check_enabled == 0 || 
			typeof e === 'undefined' || 
			!e.hasAttribute("data-changed") ) return false;

	// process the element name, get rid of []
	var enp = e.name.split("[");
	enp = enp[0];
	var enptail = '';
	var val;
	var tmp = [];

	// check if the error element actually exists, quit if it doesn't
	var er = document.getElementById(prefix+"snappy_"+enp);
	if ( er == null ) return false;
	
	// special case: array[] element, expand element selection
	if ( !e.multiple && arrayName(e.name) ) 
	{
		var el = form.querySelectorAll(e.nodeName.toLowerCase()+"[name^='"+enp+"[']");
		
		// fuzzy matching returns multiple element names ! 
		for ( var i = 0 ; i < el.length ; ++i )
		{
			val = getValue(el[i]);
			if ( val !== null ) tmp.push(el[i].name + "=" + val);
		}
		enptail = '[]';
	}
	// <select multiple> or single element
	else
	{
		tmp = getAllValues(e, tmp);
	}
	
	// form the final query
	var q = (tmp.length ? tmp.join("&") + '&' : '') + "snappy_async_mode=" + ( tmp.length ? '1' : enp+enptail) + "&myform=1";

	var le = document.getElementById(prefix+"snappy_loading_"+enp); // custom loading text position?
	request(q, er, le);
}

// gets all <select multiple> and single element values
function getAllValues(e, a)
{
	var n = encodeURIComponent(e.name);
	if ( e.multiple ) 
	{
		var t = Array.from(e.querySelectorAll("option:checked"));
		for(var j = 0; j < t.length; ++j)
		{
			a.push(n + "=" + getValue(t[j]));
		}
	}
	else
	{
		var val = getValue(e);
		if ( val != null ) a.push(n + "=" + val);
	}
	return a;
}

// displays|hides loading message 
function loadingState(er, le, s)
{
	// check for element specific loading msg positioning
	if ( le != null ) 
	{
		le.style.display = ( s ? "" : "none" );
	}
	// otherwise if 'loading' message is defined, swap contents with error message
	else if ( er != null && lm != '' )
	{
		if ( s ) 
		{
			er_tmp = er.innerHTML;
			er.style.display = '';
		}
		er.innerHTML = ( s ? lm : er_tmp);
	}
}

// display success message (if available) and reset form values
function resetForm()
{
	setVisibility("snappy_success_msg", 0);
	form.reset();
}

function request(q, er, le)
{	
	loadingState(er, le, 1); // show loading message if available
	
	var http = new XMLHttpRequest();
	http.open('POST', 'demo.php', true);

	//Send the proper header information along with the request
	http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

	http.onreadystatechange = function() {//Call a function when the state changes.
		if(http.readyState == 4 && http.status == 200) {
			loadingState(er, le, 0); // hide loading message, show error 
			const o = JSON.parse(http.responseText);
			for (const p in o) 
			{
				if ( p == 'success_msg' && !o[p] ) form.reset();
				setVisibility("snappy_"+p, o[p]);
			}
		}
	}
	http.send(q);
}

// for submitting the whole form
function processForm()
{  
	var arr = [];
	for ( var i = 0; i < form.elements.length; ++i ) 
	{
		arr = getAllValues(form.elements[i], arr);
	}
	request(arr.join("&") + "&snappy_async_mode=2", null, null);
}

// add event listeners for input and focusout events for the given form id for input, select and textarea elements
// checkElem() is executed only on 'focusout' if 'input' event is detected before that
const delegate = (selector) => (cb) => (e) => e.target.matches(selector) && cb(e);
const inputDelegate = delegate('input, select, textarea');
form.addEventListener('input', inputDelegate((el) => el.target.setAttribute("data-changed", "1")));
form.addEventListener('focusout', inputDelegate((el) => checkElem(el.target)));

form.addEventListener("submit", (event) => {
	if ( submit_enabled && form != null )
	{
		setVisibility("snappy_success_msg", 1);
		setVisibility("snappy_failure_msg", 1);
		event.preventDefault();
		processForm();
	}
  });
