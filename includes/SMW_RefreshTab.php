<?php
global $wgHooks;
$wgHooks[ 'SkinTemplateTabs' ][] = 'smwfAddRefreshTab';

/**
 * Adds an action that refreshes the article, i.e. it purges the article from
 * the cache and thus refreshes the inline queries.
 */
function smwfAddRefreshTab($obj, $content_actions) {
	global $wgUser;
	$title = $obj->mTitle;
	if ($title === NULL) { // TODO: quick fix for some MediaWiki skins
		return true;
	}
	if($wgUser->isAllowed('delete')){
		$content_actions['purge'] = array(
			'class' => false,
			'text' => wfMsg('purge'),
			'href' => $title->getLocalUrl( 'action=purge' )
		);
	}
	return true; // always return true, in order not to stop MW's hook processing!
}
?>
