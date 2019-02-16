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
class SpecialMissingRedirectAnnotationsTest extends \PHPUnit_Framework_TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp() {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( '\SMW\Store' )
			->disableOriginalConstructor()
			->setMethods( ['service'] )
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown() {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {

		$resultWrapper = $this->getMockBuilder( '\FakeResultWrapper' )
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
			->will( $this->returnValue( $resultWrapper ) );

		$this->store->expects( $this->at( 0 ) )
			->method( 'service' )
			->with( $this->equalTo( 'SortLetter' ) )
			->will( $this->returnValue( $sortLetter ) );

		$this->store->expects( $this->at( 1 ) )
			->method( 'service' )
			->with( $this->equalTo( 'MissingRedirectLookup' ) )
			->will( $this->returnValue( $missingRedirectLookup ) );

		$instance = new SpecialMissingRedirectAnnotations();

		$instance->getContext()->setTitle(
			Title::newFromText( 'SpecialMissingRedirectAnnotations' )
		);

		$instance->execute( '' );
	}

}
