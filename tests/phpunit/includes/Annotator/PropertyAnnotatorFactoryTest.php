<?php

namespace SMW\Tests\Annotator;

use SMW\Annotator\PropertyAnnotatorFactory;

use Title;

/**
 * @covers \SMW\Annotator\PropertyAnnotatorFactory
 *
 * @group SMW
 * @group SMWExtension
 *
 * @license GNU GPL v2+
 * @since 2.0
 *
 * @author mwjames
 */
class PropertyAnnotatorFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\Annotator\PropertyAnnotatorFactory',
			new PropertyAnnotatorFactory()
		);
	}

	public function testNewNullPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Annotator\NullPropertyAnnotator',
			$instance->newNullPropertyAnnotator( $semanticData )
		);
	}

	public function testNewRedirectPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$redirectTargetFinder = $this->getMockBuilder( '\SMW\MediaWiki\RedirectTargetFinder' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Annotator\RedirectPropertyAnnotator',
			$instance->newRedirectPropertyAnnotator( $semanticData, $redirectTargetFinder )
		);
	}

	public function testNewPredefinedPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$pageInfo = $this->getMockBuilder( '\SMW\PageInfo' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Annotator\PredefinedPropertyAnnotator',
			$instance->newPredefinedPropertyAnnotator( $semanticData, $pageInfo )
		);
	}

	public function testNewSortkeyPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Annotator\SortkeyPropertyAnnotator',
			$instance->newSortkeyPropertyAnnotator( $semanticData, 'Foo' )
		);
	}

	public function testNewCategoryPropertyAnnotator() {

		$semanticData = $this->getMockBuilder( '\SMW\SemanticData' )
			->disableOriginalConstructor()
			->getMock();

		$instance = new PropertyAnnotatorFactory();

		$this->assertInstanceOf(
			'\SMW\Annotator\CategoryPropertyAnnotator',
			$instance->newCategoryPropertyAnnotator( $semanticData, array() )
		);
	}

}
