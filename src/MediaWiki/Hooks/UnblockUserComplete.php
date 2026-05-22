<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\UnblockUserCompleteHook;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class UnblockUserComplete implements UnblockUserCompleteHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly UserChange $userChange ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onUnblockUserComplete( $block, $user ) {
		$this->userChange->notify( 'UnblockUserComplete', $block->getTargetUserIdentity() );

		return true;
	}

}
