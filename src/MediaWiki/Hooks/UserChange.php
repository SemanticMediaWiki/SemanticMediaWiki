<?php

namespace SMW\MediaWiki\Hooks;

use SMW\ApplicationFactory;
use SMW\MediaWiki\Jobs\UpdateJob;
use SMW\NamespaceExaminer;
use Title;
use User;

/**
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BlockIpComplete
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UnblockUserComplete
 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
 *
 * Act on events that happen outside of the normal parser process to ensure that
 * changes to pre-defined properties related to a user status can be invoked.
 *
 * @license GNU GPL v2+
 * @since 3.0
 *
 * @author mwjames
 */
class UserChange extends HookHandler {

	/**
	 * @var NamespaceExaminer
	 */
	private $namespaceExaminer;

	/**
	 * @var string
	 */
	private $origin = '';

	/**
	 * @since 3.0
	 *
	 * @param NamespaceExaminer $namespaceExaminer
	 */
	public function __construct( NamespaceExaminer $namespaceExaminer ) {
		$this->namespaceExaminer = $namespaceExaminer;
	}

	/**
	 * @since 3.0
	 *
	 * @param string $origin
	 */
	public function setOrigin( $origin ) {
		$this->origin = $origin;
	}

	/**
	 * @since 3.0
	 *
	 * @param User|string $user
	 */
	public function process( $user ) {

		if ( !$this->namespaceExaminer->isSemanticEnabled( NS_USER ) ) {
			return false;
		}

		if ( $user instanceof User ) {
			$user = $user->getName();
		}

		$updateJob = ApplicationFactory::getInstance()->newJobFactory()->newUpdateJob(
			Title::newFromText( $user, NS_USER ),
			[
				UpdateJob::FORCED_UPDATE => true,
				'origin' => $this->origin
			]
		);

		$updateJob->insert();

		return true;
	}

}
