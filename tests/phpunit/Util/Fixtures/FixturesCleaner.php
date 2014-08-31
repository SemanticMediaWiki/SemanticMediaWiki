<?php

namespace SMW\Tests\Util\Fixtures;

use SMW\Tests\Util\Fixtures\Properties\AreaProperty;
use SMW\Tests\Util\Fixtures\Properties\PopulationDensityProperty;
use SMW\Tests\Util\Fixtures\Facts\BerlinFact;
use SMW\Tests\Util\Fixtures\Facts\ParisFact;

use SMW\Tests\Util\PageDeleter;

use SMW\DIWikiPage;
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
	 */
	public function purgeSubjects( array $subjects ) {

		$pageDeleter = new PageDeleter();

		foreach ( $subjects as $subject ) {

			if ( $subject instanceOf DIWikiPage ) {
				$subject = $subject->getTitle();
			}

			if ( !$subject instanceOf Title ) {
				continue;
			}

			$pageDeleter->deletePage( $subject );
		}

		return $this;
	}

	/**
	 * @since 2.1
	 *
	 * @param array $facts
	 */
	public function purgeFacts( array $facts ) {

		foreach ( $facts as $fact ) {
			$fact->purge();
		}

		return $this;
	}

}
