<?php
global $wgHooks;
$wgHooks[ 'SkinTemplateTabs' ][] = 'smwfAddRefreshTab';

/**
 * Adds an action that refreshes the article, i.e. it purges the article from
 * the cache and thus refreshes the inline queries.
 */
function smwfAddRefreshTab($obj, $content_actions) {
	global $wgUser, $wgTitle;
	if($wgUser->isAllowed('delete')){
		$content_actions['purge'] = array(
			'class' => false,
			'text' => wfMsg('smw_purge'),
			'href' => $wgTitle->getLocalUrl( 'action=purge' )
		);
	}
	return true; // always return true, in order not to stop MW's hook processing!
}

