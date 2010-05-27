<?php
/**
 * @file
 * @ingroup SMW
 */

/*
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if ( !defined( 'MEDIAWIKI' ) ) die();
global $wgHooks;
$wgHooks[ 'SkinTemplateTabs' ][] = 'smwfAddRefreshTab'; // basic tab addition
$wgHooks[ 'SkinTemplateNavigation' ][] = 'smwfAddStructuredRefreshTab'; // structured version for "Vector"-type skins

/**
 * Extends the provided array of content actions with an action that refreshes the article,
 * i.e. it purges the article from the cache and thus refreshes the inline queries.
 */
function smwfAddRefreshTab( $skin, &$content_actions ) {
	global $wgUser;
 	if ( $wgUser->isAllowed( 'delete' ) ) {
		smwfLoadExtensionMessages( 'SemanticMediaWiki' );
		$content_actions['purge'] = array(
			'class' => false,
			'text' => wfMsg( 'smw_purge' ),
			'href' => $skin->mTitle->getLocalUrl( 'action=purge' )
		);
 	}
	return true; // always return true, in order not to stop MW's hook processing!
}

/**
 * Adds the refresh action like smwfAddRefreshTab(), but places it into
 * the structure of actions as used in new "Vector"-type skins
 */
function smwfAddStructuredRefreshTab( $skin, &$links ) {
	$actions = $links['actions'];
	smwfAddRefreshTab( $skin, $actions );
	$links['actions'] = $actions;
	return true;
}

