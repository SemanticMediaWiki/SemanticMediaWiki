<?php

##=##=##=##=## Configuration of Semantic MediaWiki ##=##=##=##=##
##
## This file contains important settings for customising your
## installation. Copy the content of this file into a new file
## "SMW_LocalSettings.php" within the same directory, and modify 
## the settings in this file as required.
##
##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##=##


###
# Set the following to the name of your server. This can 
# be something like "en.wikipedia.org" but also an IP-address. 
# The name need not be an existing domain, since it is only 
# used to generate identifiers in the RDF export. Yet a real 
# address is to be preferred.
##
$smwgServer="examplewiki.ontoworld.org";
##

###
# Set the following value to "true" if you want to enable support
# for semantic annotations within templates. For the moment, this
# will only work after a minor change in your MediaWiki files --
# see INSTALL for details.
##
$smwgEnableTemplateSupport = false;
##

###
# Here you can select in which cases you want to have an factbox
# appear below an article. The default setting is "SMW_FACTBOX_NONEMPTY"
# which shows only those factboxes that have some content. Other options:
##
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
# There are also many settings to customize inline queries, especially depending
# on how much performance you can spare for these queries. Below, you can set any
# of the variables as documented (and preset) in SMW_InlineQueries.php.
# Large sites should definitely do this to prevent overly high loads!
##
// $smwgIQEnabled = true;
// $smwgIQDefaultLinking = 'all';
// $smwgIQMaxConditions = 50;
// $smwgIQMaxTables = 10;
// ...
##

###
# If you want to import ontologies, you need to install RAP,
# a free RDF API for PHP, see 
#     http://www.wiwiss.fu-berlin.de/suhl/bizer/rdfapi/
# The following is the path to your installation of RAP 
# (the directory where you extracted the files to) as seen 
# from your local filesystem.
##
$smwgRAPPath = $smwgIP . '/libs/rdfapi-php';
//$smwgRAPPath = '/another/example/path/rdfapi-php';
##

###
# If you already have custom namespaces, change the following
# number to match the smallest even namespace number that is 
# not in use yet. However, it must not be smaller than 100.
##
smwfInitNamespaces(100);
##

###
# Here you can define for which namespaces the semantic links
# and annotations are to be evaluated. On other pages, annotations
# can be given but are silently ignored. This is useful since, 
# e.g., talk pages usually do not have attributes and the like. In 
# fact, is is not obvious what a meaningful attribute of a talk 
# page could be.
# The "false" entries are irrelevant, but included for convenience.
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
	     NS_MEDIAWIKI => true,
	NS_MEDIAWIKI_TALK => false,
	      NS_TEMPLATE => false,
	 NS_TEMPLATE_TALK => false,
	          NS_HELP => true,
	     NS_HELP_TALK => false,
	      NS_CATEGORY => true,
	 NS_CATEGORY_TALK => false,
	  SMW_NS_RELATION => true,
	 SMW_NS_ATTRIBUTE => true,
	      SMW_NS_TYPE => true
);
##

?>