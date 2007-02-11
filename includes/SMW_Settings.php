<?php

###
# This is the path to your installation of Semantic MediaWiki as
# seen from the web. Change it if required ($wgScriptPath is the
# path to the base directory of your wiki). No final slash.
##
$smwgScriptPath = $wgScriptPath . '/extensions/SemanticMediaWiki';
##

###
# This is the path to your installation of Semantic MediaWiki as
# seen on your local filesystem. Used against some PHP file path
# issues.
##
$smwgIP = $IP . '/extensions/SemanticMediaWiki';
##

// load global functions
require_once('SMW_GlobalFunctions.php');

###
# If you already have custom namespaces on your site, insert
# $smwgNamespaceIndex = ???;
# into your LocalSettings.php *before* including this file.
# The number ??? must be the smallest even namespace number 
# that is not in use yet. However, it must not be smaller 
# than 100.
##
if (!isset($smwgNamespaceIndex)) {
	smwfInitNamespaces(100);
} else {
	smwfInitNamespaces();
}

###
# This setting allows you to select in which cases you want to have a factbox
# appear below an article. The default setting is "SMW_FACTBOX_NONEMPTY"
# which shows only those factboxes that have some content. Other options:
##
$smwgShowFactbox = SMW_FACTBOX_NONEMPTY;
//$smwgShowFactbox = SMW_FACTBOX_HIDDEN; # hide always
//$smwgShowFactbox = SMW_FACTBOX_SHOWN; # show always
##

###
# Settings for RDF export
##
$smwgAllowRecursiveExport = false; // can normal users request recursive export?
$smwgExportBacklinks = true; // should backlinks be included by default?
##

###
# Overwriting the following array, you can define for which namespaces 
# the semantic links and annotations are to be evaluated. On other 
# pages, annotations can be given but are silently ignored. This is 
# useful since, e.g., talk pages usually do not have attributes and 
# the like. In fact, is is not obvious what a meaningful attribute of 
# a talk page could be. Pages without annotations will also be ignored 
# during full RDF export, unless they are referred to from another
# article.
##
$smwgNamespacesWithSemanticLinks = array(
	              NS_MAIN => true,
	              NS_TALK => false,
	              NS_USER => true,
	         NS_USER_TALK => false,
	           NS_PROJECT => true,
	      NS_PROJECT_TALK => false,
	             NS_IMAGE => true,
	        NS_IMAGE_TALK => false,
	         NS_MEDIAWIKI => false,
	    NS_MEDIAWIKI_TALK => false,
	          NS_TEMPLATE => false,
	     NS_TEMPLATE_TALK => false,
	              NS_HELP => true,
	         NS_HELP_TALK => false,
	          NS_CATEGORY => true,
	     NS_CATEGORY_TALK => false,
	      SMW_NS_RELATION => true,
	 SMW_NS_RELATION_TALK => false,
	     SMW_NS_ATTRIBUTE => true,
	SMW_NS_ATTRIBUTE_TALK => false,
	          SMW_NS_TYPE => true,
	     SMW_NS_TYPE_TALK => false
);
##


// some default settings which usually need no modification

###
# Set the following value to "true" if you want to enable support
# for semantic annotations within templates. For the moment, this
# will only work if after minor change in your MediaWiki files --
# see INSTALL for details. Enabling this is necessary for normal
# operation.
##
$smwgEnableTemplateSupport = true;
##

###
# If you want to import ontologies, you need to install RAP,
# a free RDF API for PHP, see 
#     http://www.wiwiss.fu-berlin.de/suhl/bizer/rdfapi/
# The following is the path to your installation of RAP 
# (the directory where you extracted the files to) as seen 
# from your local filesystem. Note that ontology import is
# highly experimental at the moment, and may not do what you
# extect.
##
$smwgRAPPath = $smwgIP . '/libs/rdfapi-php';
//$smwgRAPPath = '/another/example/path/rdfapi-php';
##

// load (default) settings for inline queries
require_once('SMW_InlineQueries.php');

// get local configuration ...
//require("SMW_LocalSettings.php");

?>
