function selectAll(box)
{
	box.focus();
	box.select();
};

function toUrlName(str){
	str = str.replace(/^\s+|\s+$/g, ''); // trim
	str = str.toLowerCase();
	
	str = str.replace('æ','ae');
	
	// remove accents, swap ñ for n, etc
	var from = "àáäâèéëêìíïîòóöôùúüûñç·/_,:;";
	var to   = "aaaaeeeeiiiioooouuuunc------";
	for (var i=0, l=from.length ; i<l ; i++) {
		str = str.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
	}
	
	str = str.replace(/[^a-z0-9 -]/g, '') // remove invalid chars
		.replace(/\s+/g, '-') // collapse whitespace and replace by -
		.replace(/-+/g, '-'); // collapse dashes
	
	return str;
}

function makeTabs(element)
{
	element.tabs();

	/*$.address.change(function(event){
		//console.log(event);
		console.log("$.address.change " + window.location.hash);
		if(window.location.hash != "" && window.location.hash != null){
			element.tabs("option", "active", element.find(window.location.hash.substring(1)).index()-1 );			
		}
    })*/

    // when the tab is selected update the url with the hash
    element.bind("tabsactivate", function(event, ui) {
    	//alert("bb");
    	//console.log("pushState " + ui.newTab.children().attr("href"))
    	//currentTab = ui.newTab.children().attr("href");
    	history.pushState(null,null,ui.newTab.children().attr("href"));
    	
    	//$.address.value(ui.newTab.children().attr("href"));
    })
}

function showCopyable (str) {
    $("<div class='show-copyable-dialog'><textarea>" + str + "</textarea></div>").dialog({ modal: true, resizable: true, width: 800, height: 500, title: "Copy text" });

    $(".show-copyable-dialog textarea").select();
}

$( document ).ajaxError(function(event, jqxhr, settings, thrownError) {
	if(jqxhr.statusText == "abort") return;
	
	var report = "A background HTTP request has unexpectedly failed. Please send this error report to mzabsky@gmail.com, along with any additional information pertaining to the error.\n\n"; 
	report += "You may try to reload the page and to retry the action which has failed.\n\n";		
	report += "PlaneSculptors.net Error Report (XHR request failed)\n";
	report += "Timestamp: " + Date.now() + "\n";  
	report += "Client: " + navigator.userAgent + "\n";
	report += "Location: " + window.location + "\n";
	report += "Method: " + settings.type + "\n";
	report += "Request URL: " + settings.url + "\n";
	report += "Response Status: " + jqxhr.status + " (" + jqxhr.statusText +")\n";
	report += "Response Body: \n";
	report += jqxhr.responseText;

	console.log(report);	
	
	if(jqxhr.statusText == "abort" || (jqxhr.status == 0 && jqxhr.statusText == "error")) return;
	
	showCopyable(report);
});

var hasUnsavedChanges = false;
function createEditor(selector)
{
	var el = $(selector);
	var editor = new SimpleMDE({ element: el[ 0 ], status: false, hideIcons: [ 'preview', 'side-by-side', 'guide' ] });
	
	editor.codemirror.on("change", function() { hasUnsavedChanges = true; });
	
	return editor;
}

$(window).bind('beforeunload', function() {
    if(hasUnsavedChanges){
        return "You have unsaved changes on this page. Do you want to leave this page and discard your changes or stay on this page?";
    }
});

if(isLoggedIn){
	function keepAlive()
	{
		$.getJSON( "/member-area/keep-alive", function( data ) {
			
		});
	}
	setTimeout(function(){ keepAlive(); }, 1000 * 60 * 5);
}
