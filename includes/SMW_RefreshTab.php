<?php
global $wgHooks;
$wgHooks[ 'SkinTemplateTabs' ][] = 'smwfAddRefreshTab';

/**
 * Adds an action that refreshes the article, i.e. it purges the article from
 * the cache and thus refreshes the inline queries.
 */
function smwfAddRefreshTab($obj, $content_actions) {
	global $wgUser;
	if($wgUser->isAllowed('delete')){
		$content_actions['purge'] = array(
			'class' => false,
			'text' => wfMsg('purge'),
			'href' => $obj->mTitle->getLocalUrl( 'action=purge' )
		);
	}
}
?>
