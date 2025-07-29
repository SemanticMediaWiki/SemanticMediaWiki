<?php

namespace SMW\Tests\MediaWiki\Jobs;

use SMW\DIWikiPage;
use SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob;
use SMW\Tests\SMWIntegrationTestCase;

/**
 * @covers \SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob
 * @group semantic-mediawiki
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @since 2.5
 *
 * @author mwjames
 */
class FulltextSearchTableRebuildJobTest extends SMWIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment->addConfiguration( 'smwgEnabledFulltextSearch', true );
	}

	public function testCanConstruct() {
		$title = $this->getMockBuilder( '\MediaWiki\Title\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'SMW\MediaWiki\Jobs\FulltextSearchTableRebuildJob',
			new FulltextSearchTableRebuildJob( $title )
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testRunJob( $parameters ) {
		$subject = DIWikiPage::newFromText( __METHOD__ );

		$instance = new FulltextSearchTableRebuildJob(
			$subject->getTitle(),
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

		$provider[] = [
			[ 'table' => 'Foo' ]
		];

		$provider[] = [
			[ 'mode' => 'full' ]
		];

		return $provider;
	}

}
