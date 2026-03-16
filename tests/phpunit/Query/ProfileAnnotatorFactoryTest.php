<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotatorFactory;
use SMWQuery as Query;

/**
 * @covers \SMW\Query\ProfileAnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.1
 *
 * @author mwjames
 */
class ProfileAnnotatorFactoryTest extends \PHPUnit\Framework\TestCase {

	public function testCanConstruct() {
		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotatorFactory',
			new ProfileAnnotatorFactory()
		);
	}

	public function testConstructDescriptionProfileAnnotator() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\DescriptionProfileAnnotator',
			$instance->newDescriptionProfileAnnotator( $query )
		);
	}

	public function testConstructCombinedProfileAnnotator() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator',
			$instance->newProfileAnnotator( $query, '' )
		);
	}

	public function testConstructProfileAnnotatorsWithSourceAnnotator() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->willReturn( $description );

		$query->expects( $this->once() )
			->method( 'getQuerySource' )
			->willReturn( 'Foo' );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\SourceProfileAnnotator',
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

	public function testConstructProfileAnnotatorsWithDurationAnnotator() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

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
			'\SMW\Query\ProfileAnnotators\DurationProfileAnnotator',
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

	public function testConstructProfileAnnotatorsWithStatCodeAnnotator() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

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
			'\SMW\Query\ProfileAnnotators\StatusCodeProfileAnnotator',
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

	public function testConstructCombinedProfileAnnotatorOnNullContextPage() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
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
			'\SMW\Query\ProfileAnnotator',
			$instance->newProfileAnnotator( $query, '' )
		);
	}

	public function testConstructProfileAnnotators_SchemaLink() {
		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->willReturn( DIWikiPage::newFromText( __METHOD__ ) );

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
			'\SMW\Query\ProfileAnnotators\SchemaLinkProfileAnnotator',
			$instance->newProfileAnnotator( $query, 'SomeFormat' )
		);
	}

}
