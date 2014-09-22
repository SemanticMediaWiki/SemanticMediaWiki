<?php

namespace SMW\Tests\Util\Fixtures;

/**
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class FixturesFactory {

	/**
	 * @since 2.1
	 *
	 * @return FixturesProvider
	 */
	public function newFixturesProvider() {
		return new FixturesProvider();
	}

	/**
	 * @since 2.1
	 *
	 * @return FixturesCleaner
	 */
	public function newFixturesCleaner() {
		return new FixturesCleaner();
	}

}
