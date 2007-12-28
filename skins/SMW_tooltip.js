

addOnloadHook(smw_tooltipInit); 


//these two objects needed due to the "hack" in timeline-api.js
//see the comment there
BubbleTT = new Object();
BubbleTT.Platform= new Object();

var tt; //the tooltip

var imagePath=wgScriptPath+"/extensions/SemanticMediaWiki/skins/images/";

//dimensions of persistent tooltips
var SMWTT_WIDTH_P=200;
var SMWTT_HEIGHT_P=80;

//dimensions of inline tooltips
var SMWTT_WIDTH_I=150;
var SMWTT_HEIGHT_I=50;

/*register events for the tooltips*/
function smw_tooltipInit() {
	var anchs = document.getElementsByTagName("span");
	for (var i=0; i<anchs.length; i++) {
		if(anchs[i].className=="smwttpersist")smw_makePersistentTooltip(anchs[i]);
		if(anchs[i].className=="smwttinline")smw_makeInlineTooltip(anchs[i]);
	}
}

function smw_makeInlineTooltip(a) {
	var spans = a.getElementsByTagName("span");
	a.className="smwttactiveinline";
	//make content invisible
	//done here and not in the css so that non-js clients can see it
	for (var i=0;i<spans.length;i++) {
		if(spans[i].className=="smwttcontent"){
			spans[i].style.display="none";
		}
	}
	a.onmouseover=smw_showTooltipInline;
	a.onmouseout=smw_hideTooltip;
}

function smw_makePersistentTooltip(a) {
	var spans = a.getElementsByTagName("span");
	a.className="smwttactivepersist";
	for (var i=0;i<spans.length;i++) {
		if(spans[i].className=="smwtticon"){
			img=document.createElement("img");
			img.setAttribute("src",imagePath+spans[i].innerHTML);
			img.className="smwttimg";
			a.replaceChild(img, a.firstChild);
		}
		//make content invisible
		//done here and not in the css so that non-js clients can see it
		if(spans[i].className=="smwttcontent"){
			spans[i].style.display="none";
		}
	}
	//register event with anchor
	if (BubbleTT.Platform.browser.isIE) {
		a.attachEvent("onclick", smw_showTooltipPersist);
	} else {
		a.addEventListener("click", smw_showTooltipPersist, false);
	}
}

/*display tooltip*/
function smw_showTooltipPersist(e) {
	var x; 
	var y; 
	if(BubbleTT.Platform.browser.isIE){
		c = BubbleTT.getElementCoordinates(window.event.srcElement);
		x = c.left;
		y = c.top;
	}else{
		x = e.pageX;
		y = e.pageY;
	}
	var origin = (BubbleTT.Platform.browser.isIE) ? window.event.srcElement : e.target;
	//If the anchor of the tooltip contains hmtl, the source of the event is not the anchor.
	//As we need a reference to it to get the tooltip content we need to go up the dom-tree.
	while(!(origin.className=="smwttactivepersist")){origin=origin.parentNode};

	tt = BubbleTT.createBubbleForPoint(true,origin,x,y,SMWTT_WIDTH_P,SMWTT_HEIGHT_P);
	BubbleTT.fillBubble(tt, origin);

	//unregister handler to open bubble 
	if (BubbleTT.Platform.browser.isIE) {
		origin.detachEvent("onclick", smw_showTooltipPersist);
	} else {
		origin.removeEventListener("click", smw_showTooltipPersist, false);
	}
}



function smw_showTooltipInline(e) {
	var x;
	var y;
	if(BubbleTT.Platform.browser.isIE){
		c = BubbleTT.getElementCoordinates(window.event.srcElement);
		x = c.left;
		y = c.top;
	}else{
		x = e.pageX;
		y = e.pageY;
	}
	var origin = (BubbleTT.Platform.browser.isIE) ? window.event.srcElement : e.target;
	//If the anchor of the tooltip contains hmtl, the source of the event is not the anchor.
	//As we need a reference to it to get the tooltip content we need to go up the dom-tree.
	while(!(origin.className=="smwttactiveinline"))origin=origin.parentNode;
	var doc = origin.ownerDocument;
	tt = BubbleTT.createBubbleForPoint(false,origin,x,y,SMWTT_WIDTH_I,SMWTT_HEIGHT_I);
	BubbleTT.fillBubble(tt, origin);
}



function smw_hideTooltip(){
	tt.close();
}

/**
 * gets the coordinates of the element elmt
 * used to place tooltips in IE as mouse coordinates
 * behave strangely
 */
BubbleTT.getElementCoordinates = function(elmt) {
	var left = 0;
	var top = 0;

	if (elmt.nodeType != 1) {
		elmt = elmt.parentNode;
	}

	while (elmt != null) {
		left += elmt.offsetLeft;
		top += elmt.offsetTop;
		elmt = elmt.offsetParent;
	}
	return { left: left, top: top };
};


/*==================================================================
 * code below from Simile-Timeline (util/graphics.js) modified 
 *==================================================================
 */


BubbleTT._bubbleMargins = {
	top:      33,
	bottom:   42,
	left:     33,
	right:    40
}

/*pixels from boundary of the whole bubble div to the tip of the arrow*/
BubbleTT._arrowOffsets = { 
	top:      0,
	bottom:   9,
	left:     1,
	right:    8
}

BubbleTT._bubblePadding = 15;
BubbleTT._bubblePointOffset = 15;
BubbleTT._halfArrowWidth = 18;



/*creates an empty bubble*/
BubbleTT.createBubbleForPoint = function(closingButton, origin, pageX, pageY, contentWidth, contentHeight) {
	var doc = origin.ownerDocument; 
	var bubble = {
		_closed:    false,
		_doc:       doc,
		close:      function() { 
			if (!this._closed) {
				this._doc.body.removeChild(this._div);
				this._doc = null;
				this._div = null;
				this._content = null;
				this._closed = true;
			if(closingButton){//for persistent bubble: re-attach handler to open bubble again
			if (BubbleTT.Platform.browser.isIE) {
					origin.attachEvent("onclick", smw_showTooltipPersist);
				} else {
					origin.addEventListener("click", smw_showTooltipPersist, false);
			}
		}
			}
		}
	};

	var docWidth = doc.body.offsetWidth;
	var docHeight = doc.body.offsetHeight;

	var margins = BubbleTT._bubbleMargins;
	var bubbleWidth = margins.left + contentWidth + margins.right;
	var bubbleHeight = margins.top + contentHeight + margins.bottom;

	var pngIsTranslucent =  (!BubbleTT.Platform.browser.isIE) || (BubbleTT.Platform.browser.majorVersion > 6);    

	var setImg = function(elmt, url, width, height) {
		elmt.style.position = "absolute";
		elmt.style.width = width + "px";
		elmt.style.height = height + "px";
		if (pngIsTranslucent) {
			elmt.style.background = "url(" + url + ")";
		} else {
			elmt.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + url +"', sizingMethod='crop')";
		}
	}
	var div = doc.createElement("div");
	div.style.width = bubbleWidth + "px";
	div.style.height = bubbleHeight + "px";
	div.style.position = "absolute";
	div.style.zIndex = 1000;
	bubble._div = div;

	var divInner = doc.createElement("div");
	divInner.style.width = "100%";
	divInner.style.height = "100%";
	divInner.style.position = "relative";
	div.appendChild(divInner);

	var createImg = function(url, left, top, width, height) {
		var divImg = doc.createElement("div");
		divImg.style.left = left + "px";
		divImg.style.top = top + "px";
		setImg(divImg, url, width, height);
		divInner.appendChild(divImg);
	}

	createImg(imagePath + "bubble-top-left.png", 0, 0, margins.left, margins.top);
	createImg(imagePath + "bubble-top.png", margins.left, 0, contentWidth, margins.top);
	createImg(imagePath + "bubble-top-right.png", margins.left + contentWidth, 0, margins.right, margins.top);

	createImg(imagePath + "bubble-left.png", 0, margins.top, margins.left, contentHeight);
	createImg(imagePath + "bubble-right.png", margins.left + contentWidth, margins.top, margins.right, contentHeight);

	createImg(imagePath + "bubble-bottom-left.png", 0, margins.top + contentHeight, margins.left, margins.bottom);
	createImg(imagePath + "bubble-bottom.png", margins.left, margins.top + contentHeight, contentWidth, margins.bottom);
	createImg(imagePath + "bubble-bottom-right.png", margins.left + contentWidth, margins.top + contentHeight, margins.right, margins.bottom);

	//closing button
	if(closingButton){
		var divClose = doc.createElement("div");
		divClose.style.left = (bubbleWidth - margins.right + BubbleTT._bubblePadding - 16 - 2) + "px";
		divClose.style.top = (margins.top - BubbleTT._bubblePadding + 1) + "px";
		divClose.style.cursor = "pointer";
		setImg(divClose, imagePath + "close-button.png", 16, 16);
		BubbleTT.DOM.registerEventWithObject(divClose, "click", bubble, bubble.close);
		divInner.appendChild(divClose);
	}

	var divContent = doc.createElement("div");
	divContent.style.position = "absolute";
	divContent.style.left = margins.left + "px";
	divContent.style.top = margins.top + "px";
	divContent.style.width = contentWidth + "px";
	divContent.style.height = contentHeight + "px";
	divContent.style.overflow = "auto";
	divContent.style.background = "white";
	divInner.appendChild(divContent);
	bubble.content = divContent;

	(function() {
		if (pageX - BubbleTT._halfArrowWidth - BubbleTT._bubblePadding > 0 &&
			pageX + BubbleTT._halfArrowWidth + BubbleTT._bubblePadding < docWidth) {
			
			var left = pageX - Math.round(contentWidth / 2) - margins.left;
			left = pageX < (docWidth / 2) ?
				Math.max(left, -(margins.left - BubbleTT._bubblePadding)) : 
				Math.min(left, docWidth + (margins.right - BubbleTT._bubblePadding) - bubbleWidth);
				
			if (pageY - BubbleTT._bubblePointOffset - bubbleHeight > 0) { // top
				var divImg = doc.createElement("div");
				
				divImg.style.left = (pageX - BubbleTT._halfArrowWidth - left) + "px";
				divImg.style.top = (margins.top + contentHeight) + "px";
				setImg(divImg, imagePath + "bubble-bottom-arrow.png", 37, margins.bottom);
				divInner.appendChild(divImg);
				
				div.style.left = left + "px";
				div.style.top = (pageY - BubbleTT._bubblePointOffset - bubbleHeight + 
					BubbleTT._arrowOffsets.bottom) + "px";
				
				return;
			} else if (pageY + BubbleTT._bubblePointOffset + bubbleHeight < docHeight) { // bottom
				var divImg = doc.createElement("div");
				
				divImg.style.left = (pageX - BubbleTT._halfArrowWidth - left) + "px";
				divImg.style.top = "0px";
				setImg(divImg, imagePath + "bubble-top-arrow.png", 37, margins.top);
				divInner.appendChild(divImg);
				
				div.style.left = left + "px";
				div.style.top = (pageY + BubbleTT._bubblePointOffset - 
					BubbleTT._arrowOffsets.top) + "px";
				
				return;
			}
		}

		var top = pageY - Math.round(contentHeight / 2) - margins.top;
		top = pageY < (docHeight / 2) ?
			Math.max(top, -(margins.top - BubbleTT._bubblePadding)) : 
			Math.min(top, docHeight + (margins.bottom - BubbleTT._bubblePadding) - bubbleHeight);
				
		if (pageX - BubbleTT._bubblePointOffset - bubbleWidth > 0) { // left
			var divImg = doc.createElement("div");
			
			divImg.style.left = (margins.left + contentWidth) + "px";
			divImg.style.top = (pageY - BubbleTT._halfArrowWidth - top) + "px";
			setImg(divImg, imagePath + "bubble-right-arrow.png", margins.right, 37);
			divInner.appendChild(divImg);
			
			div.style.left = (pageX - BubbleTT._bubblePointOffset - bubbleWidth +
				BubbleTT._arrowOffsets.right) + "px";
			div.style.top = top + "px";
		} else { // right
			var divImg = doc.createElement("div");
			
			divImg.style.left = "0px";
			divImg.style.top = (pageY - BubbleTT._halfArrowWidth - top) + "px";
			setImg(divImg, imagePath + "bubble-left-arrow.png", margins.left, 37);
			divInner.appendChild(divImg);
			
			div.style.left = (pageX + BubbleTT._bubblePointOffset - 
				BubbleTT._arrowOffsets.left) + "px";
			div.style.top = top + "px";
		}
	})();

	doc.body.appendChild(div);
	return bubble;
};



/*fill bubble with html content*/
BubbleTT.fillBubble = function(bubble,origin){
	doc=bubble._doc;
	div = doc.createElement("div");
	div.className = "smwtt";
	//get tooltip content 
	spans=origin.getElementsByTagName("span");
	for (i=0; i<spans.length; i++){
		/* "\n" and "<!--br-->" are replaced by "<br />" to support linebreaks 
		 * in tooltips without corrupting the page for non js-clients.
		 */
		if(spans[i].className=="smwttcontent") {
			div.innerHTML=spans[i].innerHTML.replace(/\n/g,"<br />");
			div.innerHTML=spans[i].innerHTML.replace(/<!--br-->/g,"<br />");
		}
	}
	bubble.content.appendChild(div);
}


/*==================================================================
 * all below from Simile-Timeline (util/platform.js) with classname
 * Timeline replaced by BubbleTT to avoid complications with both 
 * scripts running on the same page
 *==================================================================
 */


BubbleTT.Platform.os = {
	isMac:   false,
	isWin:   false,
	isWin32: false,
	isUnix:  false
};
BubbleTT.Platform.browser = {
	isIE:           false,
	isNetscape:     false,
	isMozilla:      false,
	isFirefox:      false,
	isOpera:        false,
	isSafari:       false,
	
	majorVersion:   0,
	minorVersion:   0
};

(function() {
	var an = navigator.appName.toLowerCase();
	var ua = navigator.userAgent.toLowerCase(); 

	/*
	 *  Operating system
	 */
	BubbleTT.Platform.os.isMac = (ua.indexOf('mac') != -1);
	BubbleTT.Platform.os.isWin = (ua.indexOf('win') != -1);
	BubbleTT.Platform.os.isWin32 = BubbleTT.Platform.isWin && (
		ua.indexOf('95') != -1 || 
		ua.indexOf('98') != -1 || 
		ua.indexOf('nt') != -1 || 
		ua.indexOf('win32') != -1 || 
		ua.indexOf('32bit') != -1
	);
	BubbleTT.Platform.os.isUnix = (ua.indexOf('x11') != -1);

	/*
	 *  Browser
	 */
	BubbleTT.Platform.browser.isIE = (an.indexOf("microsoft") != -1);
	BubbleTT.Platform.browser.isNetscape = (an.indexOf("netscape") != -1);
	BubbleTT.Platform.browser.isMozilla = (ua.indexOf("mozilla") != -1);
	BubbleTT.Platform.browser.isFirefox = (ua.indexOf("firefox") != -1);
	BubbleTT.Platform.browser.isOpera = (an.indexOf("opera") != -1);
	//BubbleTT.Platform.browser.isSafari = (an.indexOf("safari") != -1);

	var parseVersionString = function(s) {
		var a = s.split(".");
		BubbleTT.Platform.browser.majorVersion = parseInt(a[0]);
		BubbleTT.Platform.browser.minorVersion = parseInt(a[1]);
	};
	var indexOf = function(s, sub, start) {
		var i = s.indexOf(sub, start);
		return i >= 0 ? i : s.length;
	};

	if (BubbleTT.Platform.browser.isMozilla) {
		var offset = ua.indexOf("mozilla/");
		if (offset >= 0) {
			parseVersionString(ua.substring(offset + 8, indexOf(ua, " ", offset)));
		}
	}
	if (BubbleTT.Platform.browser.isIE) {
		var offset = ua.indexOf("msie ");
		if (offset >= 0) {
			parseVersionString(ua.substring(offset + 5, indexOf(ua, ";", offset)));
		}
	}
	if (BubbleTT.Platform.browser.isNetscape) {
		var offset = ua.indexOf("rv:");
		if (offset >= 0) {
			parseVersionString(ua.substring(offset + 3, indexOf(ua, ")", offset)));
		}
	}
	if (BubbleTT.Platform.browser.isFirefox) {
		var offset = ua.indexOf("firefox/");
		if (offset >= 0) {
			parseVersionString(ua.substring(offset + 8, indexOf(ua, " ", offset)));
		}
	}
})();

BubbleTT.Platform.getDefaultLocale = function() {
	return BubbleTT.Platform.clientLocale;
};

/*==================================================
 *  DOM Utility Functions
 * all below from Simile-Timeline (util/dom.js) with classname
 * Timeline replaced by BubbleTT to avoid complications with both 
 * scripts running on the same page
 *==================================================
 */

BubbleTT.DOM = new Object();

BubbleTT.DOM.registerEventWithObject = function(elmt, eventName, obj, handler) {
	BubbleTT.DOM.registerEvent(elmt, eventName, function(elmt2, evt, target) {
		return handler.call(obj, elmt2, evt, target);
	});
};

BubbleTT.DOM.registerEvent = function(elmt, eventName, handler) {
	var handler2 = function(evt) {
		evt = (evt) ? evt : ((event) ? event : null);
		if (evt) {
			var target = (evt.target) ? 
				evt.target : ((evt.srcElement) ? evt.srcElement : null);
			if (target) {
				target = (target.nodeType == 1 || target.nodeType == 9) ? 
					target : target.parentNode;
			}
			
			return handler(elmt, evt, target);
		}
		return true;
	}

	if (BubbleTT.Platform.browser.isIE) {
		elmt.attachEvent("on" + eventName, handler2);
	} else {
		elmt.addEventListener(eventName, handler2, false);
	}
};
