/*
 * jQuery Highlight plugin
 *
 * Based on highlight v3 by Johann Burkard
 * http://johannburkard.de/blog/programming/javascript/highlight-javascript-text-higlighting-jquery-plugin.html
 *
 * Code a little bit refactored and cleaned (in my humble opinion).
 * Most important changes:
 *  - has an option to highlight only entire words (wordsOnly - false by default),
 *  - has an option to be case sensitive (caseSensitive - false by default)
 *  - highlight element tag and class names can be specified in options
 *
 * Usage:
 *   // wrap every occurrance of text 'lorem' in content
 *   // with <span class='highlight'> (default options)
 *   $('#content').highlight('lorem');
 *
 *   // search for and highlight more terms at once
 *   // so you can save some time on traversing DOM
 *   $('#content').highlight(['lorem', 'ipsum']);
 *   $('#content').highlight('lorem ipsum');
 *
 *   // search only for entire word 'lorem'
 *   $('#content').highlight('lorem', { wordsOnly: true });
 *
 *   // don't ignore case during search of term 'lorem'
 *   $('#content').highlight('lorem', { caseSensitive: true });
 *
 *   // wrap every occurrance of term 'ipsum' in content
 *   // with <em class='important'>
 *   $('#content').highlight('ipsum', { element: 'em', className: 'important' });
 *
 *   // remove default highlight
 *   $('#content').unhighlight();
 *
 *   // remove custom highlight
 *   $('#content').unhighlight({ element: 'em', className: 'important' });
 *
 *
 * Copyright (c) 2009 Bartek Szopka
 *
 * Licensed under MIT license.
 *
 */

/**
 * Simple match and replace to normalize diacritics and accents
 *
 * @param  {String} text
 *
 * @return {String}
 */
var normalizeAccents = function ( text ) {
    return text
        .replace(/[áÁàÀâÂäÄãÃåÅæÆâ]/g, 'a')
        .replace(/[çÇ]/g, 'c')
        .replace(/[éÉèÈêÊëËê]/g, 'e')
        .replace(/[íÍìÌîÎïÏîĩĨĬĭ]/g, 'i')
        .replace(/[ñÑ]/g, 'n')
        .replace(/[óÓòÒôÔöÖœŒ]/g, 'o')
        .replace(/[ß]/g, 's')
        .replace(/[úÚùÙûÛüÜ]/g, 'u')
        .replace(/[ýÝŷŶŸÿ]/g, 'n')
        .replace( /έ/g, 'ε' )
        .replace( /[ύϋΰ]/g, 'υ' )
        .replace( /ό/g, 'ο' )
        .replace( /ώ/g, 'ω' )
        .replace( /ά/g, 'α' )
        .replace( /[ίϊΐ]/g, 'ι' )
        .replace( /ή/g, 'η' )
        .replace( /\n/g, ' ' )
        .replace( /á/g, 'a' )
        .replace( /é/g, 'e' )
        .replace( /í/g, 'i' )
        .replace( /ó/g, 'o' )
        .replace( /ú/g, 'u' )
        .replace( /ê/g, 'e' )
        .replace( /î/g, 'i' )
        .replace( /ô/g, 'o' )
        .replace( /è/g, 'e' )
        .replace( /ï/g, 'i' )
        .replace( /ü/g, 'u' )
        .replace( /ã/g, 'a' )
        .replace( /õ/g, 'o' )
        .replace( /ç/g, 'c' )
        .replace( /ì/g, 'i' );
}

jQuery.extend({
    highlight: function (node, re, nodeName, className, accentInsensitive ) {
        if (node.nodeType === 3) {
            var data = accentInsensitive ? normalizeAccents( node.data ) : node.data;
            var match = data.match(re);
            if (match) {
                var highlight = document.createElement(nodeName || 'span');
                highlight.className = className || 'highlight';
                var wordNode = node.splitText(match.index);
                wordNode.splitText(match[0].length);
                var wordClone = wordNode.cloneNode(true);
                highlight.appendChild(wordClone);
                wordNode.parentNode.replaceChild(highlight, wordNode);
                return 1; //skip added node in parent
            }
        } else if ((node.nodeType === 1 && node.childNodes) && // only element nodes that have children
                !/(script|style)/i.test(node.tagName) && // ignore script and style nodes
                !(node.tagName === nodeName.toUpperCase() && node.className === className)) { // skip if already highlighted
            for (var i = 0; i < node.childNodes.length; i++) {
                i += jQuery.highlight(node.childNodes[i], re, nodeName, className, accentInsensitive);
            }
        }
        return 0;
    }
});

jQuery.fn.unhighlight = function (options) {
    var settings = { className: 'highlight', element: 'span' };
    jQuery.extend(settings, options);

    return this.find(settings.element + "." + settings.className).each(function () {
        var parent = this.parentNode;
        parent.replaceChild(this.firstChild, this);
        parent.normalize();
    }).end();
};

jQuery.fn.highlight = function (words, options) {
    var settings = { className: 'highlight', element: 'span', caseSensitive: false, wordsOnly: false, accentInsensitive: true };
    jQuery.extend(settings, options);

    if (words.constructor === String) {
        words = [words];
    }
    words = jQuery.grep(words, function(word, i){
      return word != '';
    });
    words = jQuery.map(words, function(word, i) {
      return word.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
    });
    if (words.length == 0) { return this; };

    var flag = settings.caseSensitive ? "" : "i";
    var pattern = "(" + words.join("|") + ")";
    if (settings.wordsOnly) {
        pattern = "\\b" + pattern + "\\b";
    }
    var re = new RegExp(pattern, flag);

    return this.each(function () {
        jQuery.highlight(this, re, settings.element, settings.className, settings.accentInsensitive );
    });
};
