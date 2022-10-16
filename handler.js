// check if given element name attr refers to an array input
// as in it has equal number if '[' and ']'
function arrayName(n)
{
	var l = (n.match(/\[/g) || []).length;
	var r = (n.match(/\]/g) || []).length;
	return (l && l === r ? l : 0);
}

// returns given elements value property, if
// element is not a checkbox or if it's an unchecked checkbox 
function getValue(e)
{
	return ( e.getAttribute('type') === 'checkbox' && !e.checked ? null : e.value );
}

// get's given elements all values and send them to
function checkElem(e)
{
	if ( typeof e === 'undefined' ) 		return false;
	if ( !e.hasAttribute("data-changed") ) 	return false;

	// process the element name, get rid of []
	var enp = e.name.split("[");
	enp = enp[0];
	var enptail = '';
	var val;
	var tmp = [];
	
	// <select multiple> 
	if ( e.multiple ) 
	{
		var arr = Array.from(e.querySelectorAll("option:checked"));
		
		for(var i = 0; i < arr.length; ++i)
		{
			tmp.push(e.name + "=" + arr[i].value);
		}
	}
	// check if element's name hints array[]
	else if ( arrayName(e.name) ) 
	{
		var type = e.nodeName.toLowerCase();
		var elist = document.querySelectorAll("#myform "+type+"[name^='"+enp+"[']");
		
		// fuzzy matching returns multiple element names ! 
		for ( var i = 0 ; i < elist.length ; ++i )
		{
			val = getValue(elist[i]);
			if ( val !== null ) tmp.push(elist[i].name + "=" + val);
		}
		enptail = '[]';
	}
	else
	{
		// single value
		val = getValue(e);
		if ( val !== null ) tmp.push(e.name + "=" + getValue(e));
	}
	
	// form the final query
	var output = (tmp.length ? tmp.join("&") + '&' : '') + "snappy_async_mode=" + ( tmp.length ? '1' : enp+enptail);
	
	var http = new XMLHttpRequest();
	http.open('POST', 'demo.php', true);

	//Send the proper header information along with the request
	http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

	http.onreadystatechange = function() {//Call a function when the state changes.
		if(http.readyState == 4 && http.status == 200) {
			var er = document.getElementById("snappy_"+enp);
			if ( er != null )
			{
				er.style.display = ( http.responseText == "0" ? "inline" : "none");
				e.removeAttribute("data-changed");
			}
		}
	}
	http.send(output);
}

// add event listeners for input and focusout events for the given form id for input, select and textarea elements
// checkElem() is executed only on 'focusout' if 'input' event is detected before that
const delegate = (selector) => (cb) => (e) => e.target.matches(selector) && cb(e);
const inputDelegate = delegate('input, select, textarea');
myform.addEventListener('input', inputDelegate((el) => el.target.setAttribute("data-changed", "1")));
myform.addEventListener('focusout', inputDelegate((el) => checkElem(el.target)));