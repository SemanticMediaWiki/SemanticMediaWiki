<?php

namespace SMW\Tests\Query\Parser;

use SMW\DIProperty;
use SMW\DIWikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Disjunction;
use SMW\Query\Parser\DescriptionProcessor;

/**
 * @covers SMW\Query\Parser\DescriptionProcessor
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionProcessorTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'SMW\Query\Parser\DescriptionProcessor',
			new DescriptionProcessor()
		);
	}

	public function testError() {

		$instance = new DescriptionProcessor();
		$instance->addError( 'abc' );

		$this->assertEquals(
			array( 'abc' ),
			$instance->getErrors()
		);

		$instance->addErrorWithMsgKey( 'Foo' );

		$this->assertCount(
			2,
			$instance->getErrors()
		);
	}

	public function testconstructDescriptionForPropertyObjectValue() {

		$instance = new DescriptionProcessor();

		$this->assertInstanceOf(
			'SMW\Query\Language\Description',
			$instance->constructDescriptionForPropertyObjectValue( new DIProperty( 'Foo' ), 'bar' )
		);
	}

	public function testconstructDescriptionForWikiPageValueChunk() {

		$instance = new DescriptionProcessor();

		$valueDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$valueDescription
		);

		$this->assertEquals(
			new DIWikiPage( 'Bar', NS_MAIN ),
			$valueDescription->getDataItem()
		);
	}

	public function testconstructDescriptionForWikiPageValueChunkOnApproximateValue() {

		$instance = new DescriptionProcessor();

		$valueDescription = $instance->constructDescriptionForWikiPageValueChunk( '~bar' );

		$this->assertEquals(
			new DIWikiPage( 'bar', NS_MAIN ),
			$valueDescription->getDataItem()
		);
	}

	public function testGetDisjunctiveCompoundDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->constructDisjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentConjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->constructDisjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentDisjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->constructDisjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullNewDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->constructDisjunctiveCompoundDescriptionFrom( $currentDescription, null )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullCurrentDescription() {

		$instance = new DescriptionProcessor();

		$newDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->constructDisjunctiveCompoundDescriptionFrom( null, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->constructConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentConjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->constructConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentDisjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->constructConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullNewDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->constructConjunctiveCompoundDescriptionFrom( $currentDescription, null )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullCurrentDescription() {

		$instance = new DescriptionProcessor();

		$newDescription = $instance->constructDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->constructConjunctiveCompoundDescriptionFrom( null, $newDescription )
		);
	}

	public function testConstuctDescriptionWithContextPage() {

		$instance = new DescriptionProcessor();

		$instance->setContextPage(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$currentDescription = new Disjunction();

		$newDescription = $instance->constructDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->constructConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

}
