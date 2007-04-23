window.onload = fn_initmenu();

function fn_initmenu()           
{   
	smw_setcontentheight(0.8)
	new Insertion.Bottom('ontomenuanchor', createMenu() );		
}

function switchVisibility(theID) 
{
	$(theID).toggle();
	
}

function createMenu() 
{
	return "<div id=\"annotation-toolbox\">\
		<div id=\"annotator-headline\">Please help me with the following:</div>\
		<div id=\"annotator\">\
			Mark a word or small text passage (e.g. a full name) that states important facts about \"*articlename*\" by clicking and dragging your mouse in the text.\
			<br/>\
			<br/>\
			<a onmouseout=\"return nd();\" onmouseover=\"return overlib(why, BELOW);\" href=\"javascript:void(0);\">Why should I do that?</a>\
		</div>\
		<div id=\"freshfacts-headline\" onclick=\"switchVisibility('freshfacts')\">Facts about this article</div>\
		<div id=\"freshfacts\">\
			<div id=\"factsAttributes\">\
				<div id=\"factsAttributes-headline\" onclick=\"switchVisibility('factsAttributes-body')\">\
					Attributes\
				</div>\
				<div id=\"factsAttributes-body\">\
					<div id=\"factsAttributes-bodycontent\">" + smw_getAttributes() + "</div>\
				</div>\
			</div>\
			<div id=\"factsRelations\">\
				<div id=\"factsRelations-headline\" onclick=\"switchVisibility('factsRelations-body')\">Relations</div>\
				<div id=\"factsRelations-body\">\
					<div id=\"factsRelations-bodycontent\">" + smw_getRelations() + "</div>\
				</div>\
			</div>\
			<div id=\"factsCategories\">\
				<div id=\"factsCategories-headline\" onclick=\"switchVisibility('factsCategories-body')\">Categories</div>\
				<div id=\"factsCategories-body\">\
					<div id=\"factsCategories-bodycontent\">" + smw_getCategories() + "</div>\
				</div>\
			</div>\
			<a href=\"javascript:WikEdHighlightSyntax()\">Highlight</a><br> \
			You did not annotate anything yet.\
			<br/>\
			<br/>\
			<a onmouseout=\"return nd();\" onmouseover=\"return overlib(where, BELOW);\" href=\"javascript:void(0);\">Where can is see what facts are already known about this article?</a>\
		</div>\
	</div>";
	
}

function getWindowHeight()
{
    //Common for Opera&Gecko	
    if (window.innerHeight) {
        return window.innerHeight;
    } else {
	//Common for IE
        if (window.document.documentElement && window.document.documentElement.clientHeight) 
        {
            return window.document.documentElement.clientHeight;
        } else {
		//Fallback solution for IE, does not always return usable values
		if (document.body && document.body.offsetHeight) {
        		return win.document.body.offsetHeight;
	        }
		return 0;	
	}
    }
}

function smw_setcontentheight(ratio){

	$('content').setStyle({ height: getWindowHeight() * ratio + 'px' });
	$('innercontent').setStyle({ height: getWindowHeight() * ratio + 'px' });
	$('ontomenu').setStyle({ height: getWindowHeight() * ratio + 'px' });
}


//toggles if the annotation menu is visible or not	
function smw_togglemenuvisibility(){
	//Check if menu is currently hidden or shown	
	if( $('ontomenu').visible() ){
		//Hide menu
		$('ontomenu').hide();
		//Resize normal content to max minus a space of 15 pixel 
		$('innercontent').setStyle({ right: '15px' });
	} else {
		//Show menu
		$('ontomenu').show();
		//Calculate the space needed for the menu
		var rightspace = $('ontomenu').getWidth() + 30;
		//Resize the normal Content so the menu fits right to it
		$('innercontent').setStyle({ right: rightspace + 'px' });
	}

}

function smw_getAttributes(){
	//RegEx from SMW-Code but [^]] is replaced with [^\]]
	return smw_getTextItem(/(\[\[(([^:][^\]]*):=)+([^\|\]]*)(\|([^\]]*))?\]\])/);	
}


function smw_getRelations(){
	//RegEx from SMW-Code but [^]] is replaced with [^\]]
	return smw_getTextItem(/(\[\[(([^:][^\]]*)::)+([^\|\]]*)(\|([^\]]*))?\]\])/);
}

function smw_getCategories(){
	return smw_getTextItem(/(\[\[category:([^:]).*\]\])/);
}

function smw_getTextItem(regex){
	var attributes = [];
	if($('wpTextbox1')){
		var attributes = [];
		$('wpTextbox1').getValue().scan(regex, function(match){ attributes.push(match[0])});
		//$('wpTextbox1').getValue().scan(/This/, function(match){ attributes.push(match[0])});
	}
	if(attributes.last() != null){
		return attributes.inspect();	
	} else {
	return null;
	}
}
//This is just a dummy function for testing.		
function fn_dummy() {
	alert("Hello Ontoprise!");
}

function WikEdHighlightSyntax() {
	var obj;
	obj = document.getElementById("wpTextbox1");
	obj.innerHTML = obj.innerHTML.replace(/(\[\[(([^:][^\]]*):=)+([^\|\]]*)(\|([^\]]*))?\]\])/, '<span class="hlattribute">$&</span><!--Attribute-->');
}
