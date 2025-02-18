<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\MediaWiki\Jobs\NullJob;

/**
 * @covers \SMW\MediaWiki\Jobs\NullJob
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class NullJobTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\NullJob',
			new NullJob( null )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {
		$instance = new NullJob(
			null,
			$parameters
		);

		$this->assertTrue(
			$instance->run()
		);
	}

	public function parametersProvider() {
		$provider[] = [
			[]
		];

		return $provider;
	}

}
