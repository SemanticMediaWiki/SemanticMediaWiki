var ns4, ie4, ie5, kon // to check which browser 
var x,y
var toolTipElement = null // to save id of current toolTip
var px="px" // entity for position and size

var offsetX = -156; // offset of tooltip to cursor
var offsetY = -15;


/*
// only to handle problems with Netscape 4 and Konqueror
*/
function rebrowse() {
	window.location.reload();
}

/*
// We have to check the current browser in order to
// pimp the javascript ...
*/
function initToolTip() {
	ns4 = (document.layers)? true : false;
	ie4 = (document.all)? true : false;
	ie5 = ((ie4) && ((navigator.userAgent.indexOf('MSIE 5')>0) || (navigator.userAgent.indexOf('MSIE 6')>0)))? true : false;
	kon = (navigator.userAgent.indexOf('konqueror')>0)? true : false;
	x = 0;
	y = 0;
	document.onmousemove = mousemove;
	if (ns4 && document.captureEvents) document.captureEvents(Event.MOUSEMOVE);
    if (ns4 || kon) setTimeout("window.onresize = rebrowse", 2000);
	if (ns4) px="";
}

/*
// Just hiding the tooltip
*/
function hideToolTip() {
	if (toolTipElement) toolTipElement.visibility = ns4? "hide" : "hidden";
	toolTipElement = null;
}

/*
// Here we are looking for the HTML element which represents the tooltip.
// We try to find this element by iterating all elements in the document.
// This function is that complicated, since there different browsers are 
// implementing this functionality differently.
*/
function getToolTip(id) {
	if (document.layers && document.layers[id]) {
		return document.layers[id];
	}
	if (document.all && document.all[id] && document.all[id].style) {
		return document.all[id].style;
	}
	if (document[id]) {
		return document[id];
	}
	if (document.getElementById(id)) {
		return document.getElementById(id).style;
	}
	return 0;
}

/*
// This function creates a tooltip. A div-Tag containing the given text
// is written to the document and set to invisble (byx css).
*/
function createToolTip(id, text) {
	document.write('<div id="' + id + '" name="' + id + '" class="smwtt">' + text + '</div>');
}

/*
// This function is called from HTML-attribute (e.g. onMouseOver). We try to
// get the current window size in order to not set a position which is out of
// the view range. (This possibility is not used at the moment!)
*/
function showToolTip(id) {
	if (toolTipElement) hideToolTip(id);
	toolTipElement = getToolTip(id);
	setPositionAndVisible();
}

/*
// Just sets the position of the tooltip to current
// mouse position and makes the tooltip visile
*/
function setPositionAndVisible() {
	toolTipElement.left = x + offsetX + px;
	toolTipElement.top  = y + offsetY + px;
	toolTipElement.visibility = ns4? "show" : "visible";
}

/* 
// In order to move the toolTip with the cursor,
// we have to get the mouse position on each "move" event 
*/
function mousemove(e) {
	if (e) {
		x = e.pageX? e.pageX : e.clientX? e.clientX : 0;
		y = e.pageY? e.pageY : e.clientY? e.clientY : 0;
	}
	else if (event) {
		x = event.clientX;
		y = event.clientY;
	}
	else {
		x = 0;
		y = 0;
	}
	if ((ie4||ie5) && document.documentElement) {
		x += document.documentElement.scrollLeft;
		y += document.documentElement.scrollTop;
	}
	if (toolTipElement) setPositionAndVisible();
}

window.onload = initToolTip;
