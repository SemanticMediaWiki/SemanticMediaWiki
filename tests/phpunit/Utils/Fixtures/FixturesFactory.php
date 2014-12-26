<?php

namespace SMW\Tests\Utils\Fixtures;

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

	/**
	 * @since 2.1
	 *
	 * @return FixturesFileProvider
	 */
	public function newFixturesFileProvider() {
		return new FixturesFileProvider();
	}

}
