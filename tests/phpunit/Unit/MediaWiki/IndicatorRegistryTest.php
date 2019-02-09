<?php

namespace SMW\Tests\MediaWiki;

use SMW\MediaWiki\IndicatorRegistry;
use SMW\DIWikiPage;

/**
 * @covers \SMW\MediaWiki\IndicatorRegistry
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since   3.1
 *
 * @author mwjames
 */
class IndicatorRegistryTest extends \PHPUnit_Framework_TestCase {

	private $indicatorProvider;

	protected function setUp() {

		$this->indicatorProvider = $this->getMockBuilder( '\SMW\MediaWiki\IndicatorProvider' )
			->disableOriginalConstructor()
			->getMock();
	}

	public function testCanConstruct() {

		$this->assertInstanceOf(
			IndicatorRegistry::class,
			 new IndicatorRegistry()
		);
	}

	public function testAddIndicatorProvider() {

		$title = $this->getMockBuilder( '\Title' )
			->disableOriginalConstructor()
			->getMock();

		$this->indicatorProvider->expects( $this->once() )
			->method( 'hasIndicator' )
			->will( $this->returnValue( true ) );

		$this->indicatorProvider->expects( $this->once() )
			->method( 'getIndicators' )
			->will( $this->returnValue( [] ) );

		$this->indicatorProvider->expects( $this->once() )
			->method( 'getModules' )
			->will( $this->returnValue( [] ) );

		$instance = new IndicatorRegistry();
		$instance->addIndicatorProvider( $this->indicatorProvider );

		$instance->hasIndicator( $title, '' );
	}

	public function testAttachIndicators() {

		$outputPage = $this->getMockBuilder( '\OutputPage' )
			->disableOriginalConstructor()
			->getMock();

		$outputPage->expects( $this->once() )
			->method( 'setIndicators' );

		$instance = new IndicatorRegistry();
		$instance->attachIndicators( $outputPage );
	}

}
