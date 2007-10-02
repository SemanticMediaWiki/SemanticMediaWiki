/**
 * A script for scraping Simile Timeline suitable data from HTML, and
 * for inserting the required Simile scripts if needed.
 */

var smwtl;

addOnloadHook(smw_timeline_init);

function smw_timeline_init() {
	if (!document.getElementsByName) return;
	tbls = document.getElementsByTagName("div");

	for (ti=0;ti<tbls.length;ti++) {
		thisTbl = tbls[ti];
		if (((' '+thisTbl.className+' ').indexOf("smwtimeline") != -1) && (thisTbl.id)) {
			smw_make_timeline(thisTbl);
		}
	}
}

function smw_make_timeline(div) {
	// extract relevant event data:
	var eventSource = new Timeline.DefaultEventSource();
	
	var theme = Timeline.ClassicTheme.create();
	// make some elements opaque to unify look across platforms 
	// Timelines way of setting transparency fails on some browsers (some IE6s, Konquerors)
	// Also, we use "imprecise instants" to display durations, since Timeline fails if
	// only durations are used and multiple bands appear. See below.
	theme.ether.highlightColor = "#E7E7E7";
	theme.ether.highlightOpacity = 100;
	theme.event.instant.impreciseOpacity = 100;
	//theme.event.instant.icon = Timeline.urlPrefix + "images/blue-circle.png";
	//theme.ether.interval.line.opacity = 100;
	//theme.ether.interval.weekend.opacity = 100;
	//theme.ether.interval.weekend.color = "#FFFFE0";

	var childs = div.childNodes;
	var l = childs.length;
	var bands = [];
	var bandcount = 0;
	var position = new Date(); //fallback position: today;
	for (var i = 0; i < childs.length; div.removeChild(childs[0])) {
		switch (childs[i].nodeType) {
			case 1: //ELEMENT_NODE -- an event or some general data
				if (childs[i].className == "smwtlevent")
					smw_add_event(childs[i],eventSource);
				else if (childs[i].className == "smwtlband") {
					switch (childs[i].firstChild.data) {
						case "MILLISECOND": bands[bandcount] = Timeline.DateTime.MILLISECOND; break;
						case "SECOND": bands[bandcount] = Timeline.DateTime.SECOND; break;
						case "MINUTE": bands[bandcount] = Timeline.DateTime.MINUTE; break;
						case "HOUR": bands[bandcount] = Timeline.DateTime.HOUR; break;
						case "DAY": bands[bandcount] = Timeline.DateTime.DAY; break;
						case "WEEK": bands[bandcount] = Timeline.DateTime.WEEK; break;
						case "MONTH": bands[bandcount] = Timeline.DateTime.MONTH; break;
						case "YEAR": bands[bandcount] = Timeline.DateTime.YEAR; break;
						case "DECADE": bands[bandcount] = Timeline.DateTime.DECADE; break;
						case "CENTURY": bands[bandcount] = Timeline.DateTime.CENTURY; break;
						case "MILLENIUM": bands[bandcount] = Timeline.DateTime.MILLENIUM; break;
						default: bandcount--; //dont count unrecognized bands
					}
					bandcount++;
				} else if (childs[0].className == "smwtlposition") {
					position = Timeline.DateTime.parseIso8601DateTime(childs[i].firstChild.data);
				} /*else if (childs[i].className == "smwtlsize") {
					div.setAttribute("style","height: " + childs[i].firstChild.data + ";");
				}*/
				break;
			case 3:	//TEXT_NODE -- ignore text on this level
				break;
		}
		
	}

	// create bands
	var bandInfos = [];
	var bandinfo;
	for (var i = 0; i < bandcount; i++) {
		if (i == 0) {
			bandinfo = Timeline.createBandInfo({
				eventSource:    eventSource,
				width:          smw_get_bandwidth(i,bandcount), 
				intervalUnit:   bands[i], 
				intervalPixels: 100,
				date:           position,
				theme:          theme
			})
		} else {
			bandinfo = Timeline.createBandInfo({
				showEventText:  false,
				trackHeight:    0.5,
				trackGap:       0.2,
				eventSource:    eventSource,
				width:          smw_get_bandwidth(i,bandcount), 
				intervalUnit:   bands[i], 
				intervalPixels: 100,
				date:           position,
				theme:          theme
			})
			bandinfo.syncWith = 0;
			bandinfo.highlight = true;
		}
		bandInfos[i] = bandinfo;
	}

	// default band
	if (bandcount == 0) {
		bandInfos[0] = Timeline.createBandInfo({
			eventSource:    eventSource,
			width:          "100%", 
			intervalUnit:   Timeline.DateTime.MONTH, 
			intervalPixels: 100,
			theme:          theme
		})
	}

	smwtl = Timeline.create(div, bandInfos);
}

function smw_get_bandwidth(number,count) {
	switch (count) {
		case 1: if (number == 0) return "100%"; else return "0%";
		case 2:
			switch (number) {
				case 0: return "70%";
				case 1: return "30%";
				default: return "0%";
			}
		case 3:
			switch (number) {
				case 0: return "60%";
				case 1: return "25%";
				case 2: return "15%";
				default: return "0%";
			}
		default: // dont support more than 4 bands
			switch (number) {
				case 0: return "50%";
				case 1: return "25%";
				case 2: return "15%";
				case 3: return "10%";
				default: return "0%";
			}
	}
}

function smw_add_event(evspan,evs) {
	var startdate = null;
	var enddate = null;
	var desc = "";
	var ttl = "";
	var linkurl = "";
	var prefix = "";
	var postfix = "";
	var icon = Timeline.urlPrefix + "images/dull-blue-circle.png";

	var childs = evspan.childNodes;
	for (var i = 0; i < childs.length; /* manual increment below */ ) {
		if (childs[i].nodeType == 1) { //ELEMENT_NODE -- some data element
			switch (childs[i].className) {
				case "smwtlstart":
					if (childs[i].firstChild.nodeType == 3)
						startdate = childs[i].firstChild.data;
					evspan.removeChild(childs[i]);
				break;
				case "smwtlend":
					if (childs[i].firstChild.nodeType == 3)
						enddate = childs[i].firstChild.data;
					evspan.removeChild(childs[i]);
				break;
				case "smwtltitle": 
					if (childs[i].firstChild.nodeType == 3)
						ttl = childs[i].firstChild.data;
					evspan.removeChild(childs[i]);
				break;
				case "smwtlprefix": 
					if (childs[i].firstChild.nodeType == 3)
						prefix = childs[i].firstChild.data;
					evspan.removeChild(childs[i]);
				break;
				case "smwtlpostfix": 
					if (childs[i].firstChild.nodeType == 3)
						postfix = childs[i].firstChild.data;
					evspan.removeChild(childs[i]);
				break;
				case "smwtlurl": // accept both plain text and <a>, use text of <a> for title
					if (childs[i].firstChild.nodeType == 3)
						linkurl = childs[i].firstChild.data;
					else {
						linkurl = childs[i].firstChild.getAttribute("href");
						if (childs[i].firstChild.hasChildNodes())
							ttl = childs[i].firstChild.firstChild.data;
					}
					evspan.removeChild(childs[i]);
				break;
				case "smwtlcoloricon": 
					if (childs[i].firstChild.nodeType == 3) {
						switch ( childs[i].firstChild.data ) {
							case "0": icon =Timeline.urlPrefix + "images/dull-blue-circle.png";
							break;
							case "1": icon =Timeline.urlPrefix + "images/dull-red-circle.png";
							break;
							case "2": icon =Timeline.urlPrefix + "images/dull-green-circle.png";
							break;
							case "3": icon =Timeline.urlPrefix + "images/gray-circle.png";
							break;
							case "4": icon =Timeline.urlPrefix + "images/dark-blue-circle.png";
							break;
							case "5": icon =Timeline.urlPrefix + "images/dark-red-circle.png";
							break;
							case "6": icon =Timeline.urlPrefix + "images/dark-green-circle.png";
							break;
							case "7": icon =Timeline.urlPrefix + "images/blue-circle.png";
							break;
							case "8": icon =Timeline.urlPrefix + "images/red-circle.png";
							break;
							case "9": icon =Timeline.urlPrefix + "images/green-circle.png";
							break;
						}
					}
					evspan.removeChild(childs[i]);
				break;
 				default: //proceed
					i++;
			}
		}  else i++; //proceed
	}
	desc = evspan.innerHTML; // the remaining nodes form the description of the event

	var parseDateTimeFunction = evs._events.getUnit().getParser("iso8601");
	var evt = new Timeline.DefaultEventSource.Event(
		parseDateTimeFunction(startdate),
		parseDateTimeFunction(enddate),
		parseDateTimeFunction(null),
		parseDateTimeFunction(null),
		true, //( enddate == null ), // FIXME: timeline currently fails if there are only durations and mutliple bands
		prefix + ttl + postfix,
		desc,
		"", //no image
		linkurl,
		icon,
		"",
		""
	);
// TODO: must the following have a meaningful definition?
//             evt.getProperty = function(name) {
//                 return "";
//             };
	evs._events.add(evt);
}

//FIXME: Not used at the moment. Is this needed?
//addEvent(window, "resize", onResize);

// var resizeTimerID = null;
// function onResize() {
//     if (resizeTimerID == null) {
//         resizeTimerID = window.setTimeout(function() {
//             resizeTimerID = null;
//             smwtl.layout();
//         }, 500);
//     }
// }

