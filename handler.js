let sbmit = document.querySelector("[name='myform'][type='submit']");
if ( sbmit != null && prefix != null ) 
{
	let form = sbmit.form;
	// add event listeners for input and focusout events for the given form id for input, select and textarea elements
	// checkElem() is executed only on 'focusout' if 'input' event is detected before that
	if ( check_enabled ) 
	{
		const delegate = (selector) => (cb) => (e) => e.target.matches(selector) && cb(e);
		const inputDelegate = delegate('input, select, textarea');
		form.addEventListener('input', inputDelegate((el) => el.target.setAttribute("data-changed", "1")));
		form.addEventListener('focusout', inputDelegate((el) => checkElem('demo.php', form, el.target, 'myform', prefix, lm)));
	}
	
	if ( submit_enabled )
	{ 
		form.addEventListener("submit", (event) => {
			setVisibility("snappy_success_msg", 1, prefix);
			setVisibility("snappy_failure_msg", 1, prefix);
			event.preventDefault();
			processForm('demo.php', form, prefix, lm);
		});
	}
}