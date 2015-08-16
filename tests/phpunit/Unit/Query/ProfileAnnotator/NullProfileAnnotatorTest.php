<?php

namespace SMW\Tests\Query\ProfileAnnotator;

use SMW\Query\ProfileAnnotator\NullProfileAnnotator;
use SMW\Subobject;
use SMW\DIWikiPage;

/**
 * @covers \SMW\Query\ProfileAnnotator\NullProfileAnnotator
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 1.9
 *
 * @author mwjames
 */
class NullProfileAnnotatorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$subobject = $this->getMockBuilder( '\SMW\Subobject' )
			->disableOriginalConstructor()
			->getMock();

		$this->assertInstanceOf(
			'\SMW\Query\ProfileAnnotator\NullProfileAnnotator',
			new NullProfileAnnotator( $subobject, 'abc' )
		);
	}

	public function testMethodAccess() {

		$subobject = new Subobject( DIWikiPage::newFromText( __METHOD__ )->getTitle() );

		$instance = new NullProfileAnnotator(
			$subobject,
			'_QUERYadcb944aa33b2c972470b73964c547c0'
		);

		$instance->addAnnotation();

		$this->assertInstanceOf(
			'\SMW\DIProperty',
			$instance->getProperty()
		);

		$this->assertInstanceOf(
			'\SMWDIContainer',
			$instance->getContainer()
		);

		$this->assertInstanceOf(
			'\SMWContainerSemanticData',
			$instance->getSemanticData()
		);

		$this->assertEmpty(
			$instance->getErrors()
		);

		$this->assertEquals(
			'_QUERYadcb944aa33b2c972470b73964c547c0',
			$subobject->getSubobjectId()
		);
	}

}
