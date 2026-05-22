<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Hook\BlockIpCompleteHook;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
 *
 * @license GPL-2.0-or-later
 * @since 7.0.0
 */
class BlockIpComplete implements BlockIpCompleteHook {

	/**
	 * @since 7.0.0
	 */
	public function __construct( private readonly UserChange $userChange ) {
	}

	/**
	 * @since 7.0.0
	 */
	public function onBlockIpComplete( $block, $user, $priorBlock ) {
		$this->userChange->notify( 'BlockIpComplete', $block->getTargetUserIdentity() );

		return true;
	}

}
