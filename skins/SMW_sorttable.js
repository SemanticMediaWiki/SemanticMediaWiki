// Developed by Stuart Langridge, shared under the MIT license
// from http://www.kryogenix.org/code/browser/sorttable/
// Modified for SMW (in fact, we might rewrite the code since we
// usually have very special tables to sort)

addOnloadHook(smw_sortables_init);

var SORT_COLUMN_INDEX;
var SMW_PATH;

function smw_sortables_init() {
	// The following is a hack to find out the path to our skin directory
	// I am happy to change this into anything else if there is another way ...
// 	if (!document.getElementById) return;
// 	st = document.getElementById("SMW_sorttable_script_inclusion");
// 	SMW_PATH = st.src.substring(0, st.src.length-17);
	SMW_PATH = wgScriptPath + "/extensions/SemanticMediaWiki/skins/";
	// Preload images
	smw_preload_images();
	// Now find the tables
	//if (!document.getElementsByTagName) return;
	//tbls = document.getElementsByTagName("SMW_headscript_sorttable");
    // Find all tables with class smwtable and make them sortable
    if (!document.getElementsByTagName) return;
    tbls = document.getElementsByTagName("table");
    for (ti=0;ti<tbls.length;ti++) {
        thisTbl = tbls[ti];
        if (((' '+thisTbl.className+' ').indexOf("smwtable") != -1) && (thisTbl.id)) {
            //initTable(thisTbl.id);
            smw_makeSortable(thisTbl);
        }
    }
}

function smw_preload_images() {
	// preload icons needed by SMW
	if (document.images) {
		pic1= new Image(12,14);
		pic1.src = SMW_PATH + "/images/sort_up.gif";
		pic2= new Image(12,14);
		pic2.src = SMW_PATH + "/images/sort_down.gif";
		pic3= new Image(16,16); 
		pic3.src = SMW_PATH + "/images/search_icon.png"; // TODO: move this preload to somewhere else?
	}
}

function smw_makeSortable(table) {
    if (table.rows && table.rows.length > 0) {
        var firstRow = table.rows[0];
    }
    if (!firstRow) return;
    if ( (firstRow.cells.length==0)||(firstRow.cells[0].tagName.toLowerCase() != 'th') ) return;

    // We have a first row that is a header; make its contents clickable links:
    for (var i=0;i<firstRow.cells.length;i++) {
        var cell = firstRow.cells[i];
        //var txt = smw_getInnerText(cell); // unused -- we preserve the inner html
        cell.innerHTML = '<a href="#" class="sortheader" '+
        'onclick="smw_resortTable(this, '+i+');return false;">' +
        '<span class="sortarrow"><img alt="[&lt;&gt;]" src="' + SMW_PATH + '/images/sort_none.gif"/></span></a>&nbsp;<span style="margin-left: 0.3em; margin-right: 1em;">' + cell.innerHTML + '</span>'; // the &nbsp; is for Opera ...
    }
}

function smw_getInnerText(el) {
	if (typeof el == "string") return el;
	if (typeof el == "undefined") { return el };
	if (el.innerText) return el.innerText;	//Not needed but it is faster
	var str = "";

	var cs = el.childNodes;
	var l = cs.length;
	for (var i = 0; i < l; i++) {
		switch (cs[i].nodeType) {
			case 1: //ELEMENT_NODE
				str += smw_getInnerText(cs[i]);
				break;
			case 3:	//TEXT_NODE
				str += cs[i].nodeValue;
				break;
		}
	}
	return str;
}

function smw_resortTable(lnk,clid) {
    // get the span
    var span;
    for (var ci=0;ci<lnk.childNodes.length;ci++) {
        if (lnk.childNodes[ci].tagName && lnk.childNodes[ci].tagName.toLowerCase() == 'span') span = lnk.childNodes[ci];
    }
    var spantext = smw_getInnerText(span);
    var td = lnk.parentNode;
    var column = clid || td.cellIndex;
    var table = smw_getParent(td,'TABLE');

    // Work out a type for the column
    if (table.rows.length <= 1) return;
    var itm = smw_getInnerText(table.rows[1].cells[column]);
    sortfn = smw_sort_caseinsensitive;
    if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d\d\d$/)) sortfn = smw_sort_date;
    if (itm.match(/^\d\d[\/-]\d\d[\/-]\d\d$/)) sortfn = smw_sort_date;
    if (itm.match(/^[�$]/)) sortfn = smw_sort_currency;
    if (itm.match(/^[\d\.]+$/)) sortfn = smw_sort_numeric;
    SORT_COLUMN_INDEX = column;
    var firstRow = new Array();
    var newRows = new Array();
    var footers = new Array();
    for (i=0;i<table.rows[0].length;i++) { firstRow[i] = table.rows[0][i]; }
    // class "sortbottom" makes rows sort below all others, but they are still sorted
    // class "smwfooter" excludes rows from sorting and appends them below in unchanged order
    for (j=1;j<table.rows.length;j++) {
       if ((!table.rows[j].className || table.rows[j].className.indexOf('smwfooter') == -1)) { newRows.push(table.rows[j]); } else { footers.push(table.rows[j]); }
    }

    newRows.sort(sortfn);

    var ARROW;
    if (span.getAttribute("sortdir") == 'down') {
        ARROW = '<img alt="[&gt;]" src="' + SMW_PATH + '/images/sort_up.gif"/>';
        newRows.reverse();
        span.setAttribute('sortdir','up');
    } else {
        ARROW = '<img alt="[&lt;]" src="' + SMW_PATH + '/images/sort_down.gif"/>';
        span.setAttribute('sortdir','down');
    }

    // We appendChild rows that already exist to the tbody, so it moves them rather than creating new ones
    // don't do sortbottom rows
    for (i=0;i<newRows.length;i++) { if (!newRows[i].className || (newRows[i].className && (newRows[i].className.indexOf('sortbottom') == -1))) table.tBodies[0].appendChild(newRows[i]);}
    // do sortbottom rows only
    for (i=0;i<newRows.length;i++) { if (newRows[i].className && (newRows[i].className.indexOf('sortbottom') != -1)) table.tBodies[0].appendChild(newRows[i]);}
    for (i=0;i<footers.length;i++) { table.tBodies[0].appendChild(footers[i]);}

    // Delete any other arrows there may be showing
    var allspans = document.getElementsByTagName("span");
    for (var ci=0;ci<allspans.length;ci++) {
        if (allspans[ci].className == 'sortarrow') {
            if (smw_getParent(allspans[ci],"table") == smw_getParent(lnk,"table")) { // in the same table as us?
                allspans[ci].innerHTML = '<img alt="[&lt;&gt;]" src="' + SMW_PATH + '/images/sort_none.gif"/>';
            }
        }
    }

    span.innerHTML = ARROW;
}

function smw_getParent(el, pTagName) {
	if (el == null) return null;
	else if (el.nodeType == 1 && el.tagName.toLowerCase() == pTagName.toLowerCase())	// Gecko bug, supposed to be uppercase
		return el;
	else
		return smw_getParent(el.parentNode, pTagName);
}
function smw_sort_date(a,b) {
    // y2k notes: two digit years less than 50 are treated as 20XX, greater than 50 are treated as 19XX
    aa = smw_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    bb = smw_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    if (aa.length == 10) {
        dt1 = aa.substr(6,4)+aa.substr(3,2)+aa.substr(0,2);
    } else {
        yr = aa.substr(6,2);
        if (parseInt(yr) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt1 = yr+aa.substr(3,2)+aa.substr(0,2);
    }
    if (bb.length == 10) {
        dt2 = bb.substr(6,4)+bb.substr(3,2)+bb.substr(0,2);
    } else {
        yr = bb.substr(6,2);
        if (parseInt(yr) < 50) { yr = '20'+yr; } else { yr = '19'+yr; }
        dt2 = yr+bb.substr(3,2)+bb.substr(0,2);
    }
    if (dt1==dt2) return 0;
    if (dt1<dt2) return -1;
    return 1;
}

function smw_sort_currency(a,b) {
    aa = smw_getInnerText(a.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    bb = smw_getInnerText(b.cells[SORT_COLUMN_INDEX]).replace(/[^0-9.]/g,'');
    return parseFloat(aa) - parseFloat(bb);
}

function smw_sort_numeric(a,b) {
    aa = parseFloat(smw_getInnerText(a.cells[SORT_COLUMN_INDEX]));
    if (isNaN(aa)) aa = 0;
    bb = parseFloat(smw_getInnerText(b.cells[SORT_COLUMN_INDEX]));
    if (isNaN(bb)) bb = 0;
    return aa-bb;
}

function smw_sort_caseinsensitive(a,b) {
    aa = smw_getInnerText(a.cells[SORT_COLUMN_INDEX]).toLowerCase();
    bb = smw_getInnerText(b.cells[SORT_COLUMN_INDEX]).toLowerCase();
    if (aa==bb) return 0;
    if (aa<bb) return -1;
    return 1;
}

function smw_sort_default(a,b) {
    aa = smw_getInnerText(a.cells[SORT_COLUMN_INDEX]);
    bb = smw_getInnerText(b.cells[SORT_COLUMN_INDEX]);
    if (aa==bb) return 0;
    if (aa<bb) return -1;
    return 1;
}


function addEvent(elm, evType, fn, useCapture)
// addEvent and removeEvent
// cross-browser event handling for IE5+,  NS6 and Mozilla
// By Scott Andrew
{
  if (elm.addEventListener){
    elm.addEventListener(evType, fn, useCapture);
    return true;
  } else if (elm.attachEvent){
    var r = elm.attachEvent("on"+evType, fn);
    return r;
  } else {
    alert("Handler could not be removed");
  }
}
