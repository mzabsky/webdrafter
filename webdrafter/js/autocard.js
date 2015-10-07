function bindAutocard(){
	$('.autocard').tooltipster({
	    content: 'Loading...',
	    theme: 'tooltipster-light',
	    //autoClose: false,
	    animation: 'fade',
	    updateAnimation: false,
	    speed: 50,
	    positionTracker: true,
	    functionBefore: function(origin, continueTooltip) {

	        // we'll make this function asynchronous and allow the tooltip to go ahead and show the loading notification while fetching our data
	        continueTooltip();
	        
	        // next, we want to check if our data has already been cached
	        if (origin.data('ajax') !== 'cached') {
	            $.ajax({
	                type: 'GET',
	                url: $(origin).attr("href") + "&ajax",
	                success: function(data) {
	                    // update our tooltip content with our returned data and cache it
	                    origin.tooltipster('content', $(data)).data('ajax', 'cached');
	                }
	            });
	        }
	    }
	});
}

$(document).ready(function(){
	bindAutocard();
});