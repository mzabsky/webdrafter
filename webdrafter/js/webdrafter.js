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

	$.address.change(function(event){
		if(window.location.hash != "" && window.location.hash != null){
			element.tabs("option", "active", element.find(window.location.hash).index()-1 );
		}
		
    })

    // when the tab is selected update the url with the hash
    element.bind("tabsactivate", function(event, ui) {
    	//alert("bb");
    	history.pushState(null,null,ui.newTab.children().attr("href"));
    })
}