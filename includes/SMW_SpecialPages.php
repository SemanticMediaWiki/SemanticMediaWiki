<?php

/**
 * Methods for registering special pages without pulling in their code before it is
 * actually needed.
 *
 * @author Markus Krötzsch
 */

global $IP;
include_once( "$IP/includes/SpecialPage.php" );

// Ask special

SpecialPage::addPage( new SpecialPage('Ask','',true,'doSpecialAsk',false) );

function doSpecialAsk() {
	wfProfileIn('doSpecialAsk (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/AskSpecial/SMW_SpecialAsk.php');
	SMW_AskPage::execute();
	wfProfileOut('doSpecialAsk (SMW)');
}

// Browse special

SpecialPage::addPage( new SpecialPage('Browse','',true,'doSpecialBrowse','default',true) );

function doSpecialBrowse($query = '') {
	wfProfileIn('doSpecialBrowse (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/SearchTriple/SMW_SpecialBrowse.php');
	SMW_SpecialBrowse::execute($query);
	wfProfileOut('doSpecialBrowse (SMW)');
}

// Property value special

SpecialPage::addPage( new SpecialPage('PageProperty','',FALSE,'doSpecialPageProperty',false) );

function doSpecialPageProperty($query = '') {
	wfProfileIn('doSpecialPageProperty (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/SearchTriple/SMW_SpecialPageProperty.php');
	SMW_PageProperty::execute($query);
	wfProfileOut('doSpecialPageProperty (SMW)');
}

// Property search special

SpecialPage::addPage( new SpecialPage('SearchByProperty','',true,'doSpecialSearchByProperty',false) );

function doSpecialSearchByProperty($query = '') {
	wfProfileIn('doSpecialSearchByProperty (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/SearchTriple/SMW_SpecialSearchByProperty.php');
	SMW_SearchByProperty::execute($query);
	wfProfileOut('doSpecialSearchByProperty (SMW)');
}

// URI resolver special

SpecialPage::addPage( new SpecialPage('URIResolver','',false,'doSpecialURIResolver',false) );

function doSpecialURIResolver($name = '') {
	wfProfileIn('doSpecialURIResolver (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/URIResolver/SMW_SpecialURIResolver.php');
	SMW_URIResolver::execute($name);
	wfProfileOut('doSpecialURIResolver (SMW)');
}

// RDF Export special

SpecialPage::addPage( new SpecialPage('ExportRDF','',true,'doSpecialExportRDF',false) );

function doSpecialExportRDF($page = '') {
	wfProfileIn('doSpecialExportRDF (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/ExportRDF/SMW_SpecialExportRDF.php');
	smwfExportRDF($page);
	wfProfileOut('doSpecialExportRDF (SMW)');
}

// Properties special

SpecialPage::addPage( new SpecialPage('Properties','',true,'doSpecialProperties',false) );

function doSpecialProperties($par = null) {
	wfProfileIn('doSpecialProperties (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/QueryPages/SMW_SpecialProperties.php');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new PropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	wfProfileOut('doSpecialProperties (SMW)');
	return $result;
}

// Unused Properties special

SpecialPage::addPage( new SpecialPage('UnusedProperties','',true,'doSpecialUnusedProperties',false) );

function doSpecialUnusedProperties($par = null) {
	wfProfileIn('doSpecialUnusedProperties (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/QueryPages/SMW_SpecialUnusedProperties.php');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new UnusedPropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	wfProfileOut('doSpecialUnusedProperties (SMW)');
	return $result;
}

// Wanted Properties special

SpecialPage::addPage( new SpecialPage('WantedProperties','',true,'doSpecialWantedProperties',false) );

function doSpecialWantedProperties($par = null) {
	wfProfileIn('doSpecialWantedProperties (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/QueryPages/SMW_SpecialWantedProperties.php');
	list( $limit, $offset ) = wfCheckLimits();
	$rep = new WantedPropertiesPage();
	$result = $rep->doQuery( $offset, $limit );
	wfProfileOut('doSpecialWantedProperties (SMW)');
	return $result;
}

// Admin special

///TODO: should these be messages?
global $wgMessageCache;
$wgMessageCache->addMessages(array('smwadmin' => 'Admin functions for Semantic MediaWiki'));

SpecialPage::addPage( new SpecialPage('SMWAdmin','delete',true,'doSpecialSMWAdmin',false) );

function doSpecialSMWAdmin($par = null) {
	wfProfileIn('doSpecialSMWAdmin (SMW)');
	global $smwgIP;
	include_once($smwgIP . '/specials/SMWAdmin/SMW_SpecialSMWAdmin.php');
	smwfSMWAdmin($par);
	wfProfileOut('doSpecialSMWAdmin (SMW)');
}
