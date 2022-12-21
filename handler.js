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
	
	form.addEventListener("submit", (event) => {
		if ( rsv != '' ) sv_og = set_submit_value(sbmit, rsv);
		if ( submit_enabled )
		{ 
			if ( rsv != '' ) sbmit.disabled = true;
			setVisibility(prefix+"snappy_success_msg", 0);
			setVisibility(prefix+"snappy_failure_msg", 0);
			event.preventDefault();
			processForm('demo.php', form, prefix, lm, sbmit);
		}
	});
	
}