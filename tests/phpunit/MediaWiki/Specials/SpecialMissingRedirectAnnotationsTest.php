<?php

namespace SMW\Tests\MediaWiki\Specials;

use SMW\MediaWiki\Specials\SpecialMissingRedirectAnnotations;
use SMW\Tests\TestEnvironment;
use Title;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialMissingRedirectAnnotations
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialMissingRedirectAnnotationsTest extends \PHPUnit\Framework\TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->onlyMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {
		$resultWrapper = $this->getMockBuilder( '\Wikimedia\Rdbms\FakeResultWrapper' )
			->disableOriginalConstructor()
			->getMock();

		$sortLetter = $this->getMockBuilder( '\SMW\SortLetter' )
			->disableOriginalConstructor()
			->getMock();

		$missingRedirectLookup = $this->getMockBuilder( '\SMW\SQLStore\Lookup\MissingRedirectLookup' )
			->disableOriginalConstructor()
			->getMock();

		$missingRedirectLookup->expects( $this->once() )
			->method( 'findMissingRedirects' )
			->willReturn( $resultWrapper );

		$this->store->expects( $this->at( 0 ) )
			->method( 'service' )
			->with( 'SortLetter' )
			->willReturn( $sortLetter );

		$this->store->expects( $this->at( 1 ) )
			->method( 'service' )
			->with( 'MissingRedirectLookup' )
			->willReturn( $missingRedirectLookup );

		$instance = new SpecialMissingRedirectAnnotations();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialMissingRedirectAnnotations' )
		);

		$instance->execute( '' );
	}

}
