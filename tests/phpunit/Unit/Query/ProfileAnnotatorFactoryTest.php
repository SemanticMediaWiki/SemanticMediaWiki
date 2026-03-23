<?php

namespace SMW\Tests\Unit\Query;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Description;
use SMW\Query\ProfileAnnotator;
use SMW\Query\ProfileAnnotatorFactory;
use SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator;
use SMW\Query\ProfileAnnotators\DurationProfileAnnotator;
use SMW\Query\ProfileAnnotators\SchemaLinkProfileAnnotator;
use SMW\Query\ProfileAnnotators\SourceProfileAnnotator;
use SMW\Query\ProfileAnnotators\StatusCodeProfileAnnotator;
use SMW\Query\Query;

/**
 * @covers \SMW\Query\ProfileAnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ProfileAnnotatorFactoryTest extends TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			ProfileAnnotatorFactory::class,
			new ProfileAnnotatorFactory()
		);
	}

	public function testConstructDescriptionProfileAnnotator() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			DescriptionProfileAnnotator::class,
			$instance->newDescriptionProfileAnnotator( $query )
		);
	}

	public function testConstructCombinedProfileAnnotator() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			ProfileAnnotator::class,
			$instance->newProfileAnnotator( $query, '' )
		);
	}

	public function testConstructProfileAnnotatorsWithSourceAnnotator() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->once() )
			->method( 'getQuerySource' )
			->willReturn( 'Foo' );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			SourceProfileAnnotator::class,
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

	public function testConstructProfileAnnotatorsWithDurationAnnotator() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->willReturnCallback( static function ( $key ) {
				if ( $key === Query::PROC_QUERY_TIME ) {
					return 42;
				}
				return false;
			} );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			DurationProfileAnnotator::class,
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

	public function testConstructProfileAnnotatorsWithStatCodeAnnotator() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->willReturnCallback( static function ( $key ) {
				if ( $key === Query::PROC_STATUS_CODE ) {
					return [ 100 ];
				}
				return false;
			} );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			StatusCodeProfileAnnotator::class,
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

	public function testConstructCombinedProfileAnnotatorOnNullContextPage() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( null );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			ProfileAnnotator::class,
			$instance->newProfileAnnotator( $query, '' )
		);
	}

	public function testConstructProfileAnnotators_SchemaLink() {
		$description = $this->getMockBuilder( Description::class )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( Query::class )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( WikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->atLeastOnce() )
			->method( 'getOption' )
			->willReturnCallback( static function ( $key ) {
				if ( $key === 'schema_link' ) {
					return 'Foo';
				}
				return false;
			} );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			SchemaLinkProfileAnnotator::class,
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

}
