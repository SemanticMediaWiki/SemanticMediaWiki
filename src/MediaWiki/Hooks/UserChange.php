<?php

namespace SMW\MediaWiki\Hooks;

use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use SMW\MediaWiki\JobFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\NamespaceExaminer;

/**
 * Helper used by BlockIpComplete, UnblockUserComplete and UserGroupsChanged
 * to schedule an update of the related user page.
 *
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
 *
 * @license GPL-2.0-or-later
 * @since 3.0
 *
 * @author mwjames
 */
class UserChange {

	/**
	 * @since 7.0.0
	 */
	public function __construct(
		private readonly NamespaceExaminer $namespaceExaminer,
		private readonly JobFactory $jobFactory,
	) {
	}

	/**
	 * Schedule an update job for the user page identified by $user.
	 *
	 * @since 7.0.0
	 *
	 * @param string $origin Identifier of the hook that triggered the update
	 * @param UserIdentity|string|null $user
	 */
	public function notify( string $origin, $user ): bool {
		if ( !$this->namespaceExaminer->isSemanticEnabled( NS_USER ) ) {
			return false;
		}

		// getTargetUserIdentity returns null if it is not user(eg. CIDR)
		// https://github.com/SemanticMediaWiki/SemanticMediaWiki/issues/5263
		if ( $user === null ) {
			return false;
		}

		if ( $user instanceof UserIdentity ) {
			$user = $user->getName();
		}

		$updateJob = $this->jobFactory->newUpdateJob(
			Title::newFromText( $user, NS_USER ),
			[
				UpdateJob::FORCED_UPDATE => true,
				'origin' => $origin
			]
		);

		$updateJob->lazyPush();

		return true;
	}

}
