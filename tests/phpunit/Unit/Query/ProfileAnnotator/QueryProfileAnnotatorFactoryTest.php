<?php

namespace SMW\Tests\Query\ProfileAnnotator;

use SMW\DIWikiPage;
use SMW\Query\ProfileAnnotator\QueryProfileAnnotatorFactory;

/**
 * @covers \SMW\Query\ProfileAnnotator\QueryProfileAnnotatorFactory
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.1
 *
 * @author mwjames
 */
class QueryProfileAnnotatorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\QueryProfileAnnotatorFactory',
			new QueryProfileAnnotatorFactory()
		);
	}

	public function testConstructJointProfileAnnotator() {

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

		$instance = new QueryProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\ProfileAnnotator',
			$instance->newJointProfileAnnotator( $query, '' )
		);
	}

	public function testConstructJointProfileAnnotatorOnNullContextPage() {

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

		$instance = new QueryProfileAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\ProfileAnnotator',
			$instance->newJointProfileAnnotator( $query, '' )
		);
	}

}
