function bindAutocard(){
	/*$('.autocard').tooltipster({
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
	});*/
	
	$(document).tooltip({
		items: ".autocard",
		track: true,
		show: false,
		hide: false,
		content: function(){
			var element = $(this);
			var image = $("<img src='" + (element.attr("href") != null ? element.attr("href") : element.attr("src")) + "&image' style='background: url(/images/spinner.svg); min-width: 160px; min-height: 160px; ' class='autocard-image-content'>");
			//image.
			return image;
		},
		using: function( position, feedback ) {
          $( this ).css( position );
          $( "<div>" )
            .removeClass( "arrow" )
            .addClass( feedback.vertical )
            .addClass( feedback.horizontal )
            .appendTo( this );
        }
	});

}

$(document).ready(function(){
	bindAutocard();
});

