<?php

namespace SMW\Tests\Utils\Fixtures;

use SMW\Tests\Utils\UtilityFactory;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixturesCleaner {

	/**
	 * @since 2.1
	 *
	 * @param array $subjects
	 *
	 * @return FixturesCleaner
	 */
	public function purgeSubjects( array $subjects ) {

		$pageDeleter = UtilityFactory::getInstance()->newPageDeleter();
		$pageDeleter->doDeletePoolOfPages( $subjects );

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @return FixturesCleaner
	 */
	public function purgeAllKnownFacts() {
		$fixturesProvider = new FixturesProvider();
		return $this->purgeFacts( $fixturesProvider->getListOfFactsheetInstances() );
	}

	/**
	 * @since 2.1
	 *
	 * @param array $facts
	 *
	 * @return FixturesCleaner
	 */
	public function purgeFacts( array $facts ) {

		foreach ( $facts as $fact ) {
			$fact->purge();
		}

		return $this;
	}

}
