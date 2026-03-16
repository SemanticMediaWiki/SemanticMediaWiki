<?php

namespace SMW\Tests\MediaWiki\Specials;

use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use SMW\MediaWiki\Specials\SpecialMissingRedirectAnnotations;
use SMW\SortLetter;
use SMW\SQLStore\Lookup\MissingRedirectLookup;
use SMW\Store;
use SMW\Tests\TestEnvironment;
use Wikimedia\Rdbms\FakeResultWrapper;

/**
 * @covers \SMW\MediaWiki\Specials\SpecialMissingRedirectAnnotations
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class SpecialMissingRedirectAnnotationsTest extends TestCase {

	private $testEnvironment;
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->testEnvironment = new TestEnvironment();

		$this->store = $this->getMockBuilder( Store::class )
			->disableOriginalConstructor()
			->setMethods( [ 'service' ] )
			->getMockForAbstractClass();

		$this->testEnvironment->registerObject( 'Store', $this->store );
	}

	protected function tearDown(): void {
		$this->testEnvironment->tearDown();
		parent::tearDown();
	}

	public function testCanExecute() {
		$resultWrapper = $this->getMockBuilder( FakeResultWrapper::class )
			->disableOriginalConstructor()
			->getMock();

		$sortLetter = $this->getMockBuilder( SortLetter::class )
			->disableOriginalConstructor()
			->getMock();

		$missingRedirectLookup = $this->getMockBuilder( MissingRedirectLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$missingRedirectLookup->expects( $this->once() )
			->method( 'findMissingRedirects' )
			->willReturn( $resultWrapper );

		$this->store->expects( $this->exactly( 2 ) )
			->method( 'service' )
			->willReturnCallback( static function ( $key ) use ( $sortLetter, $missingRedirectLookup ) {
				$map = [
					'SortLetter'           => $sortLetter,
					'MissingRedirectLookup' => $missingRedirectLookup,
				];
				return $map[$key] ?? null;
			} );

		$instance = new SpecialMissingRedirectAnnotations();

		$instance->getContext()->setTitle(
			MediaWikiServices::getInstance()->getTitleFactory()->newFromText( 'SpecialMissingRedirectAnnotations' )
		);

		$instance->execute( '' );
	}

}
