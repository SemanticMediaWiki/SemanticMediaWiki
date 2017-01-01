<?php

namespace SMW\Tests\Query;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotatorFactory;

/**
 * @covers \SMW\Query\ProfileAnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class ProfileAnnotatorFactoryTest extends \PHPUnit_Framework_TestCase {

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
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

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
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator',
			$instance->newCombinedProfileAnnotator( $query, '' )
		);
	}

	public function testConstructCombinedWithOtherMergableProfileAnnotators() {

		$description = $this->getMockBuilder( '\SMW\Query\Language\Description' )
			->disableOriginalConstructor()
			->getMock();

		$query = $this->getMockBuilder( '\SMWQuery' )
			->disableOriginalConstructor()
			->getMock();

		$query->expects( $this->atLeastOnce() )
			->method( 'getContextPage' )
			->will( $this->returnValue( DIWikiPage::newFromText( __METHOD__ ) ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$query->expects( $this->once() )
			->method( 'getQuerySource' )
			->will( $this->returnValue( 'Foo' ) );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotators\SourceProfileAnnotator',
			$instance->newCombinedProfileAnnotator( $query, 'SomeFormat' )
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
			->will( $this->returnValue( null ) );

		$query->expects( $this->once() )
			->method( 'getDescription' )
			->will( $this->returnValue( $description ) );

		$instance = new ProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator',
			$instance->newCombinedProfileAnnotator( $query, '' )
		);
	}

}
