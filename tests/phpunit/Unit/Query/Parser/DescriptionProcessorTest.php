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
			DescriptionProcessor::class,
			new DescriptionProcessor()
		);
	}

	public function testError() {

		$instance = new DescriptionProcessor();
		$instance->addError( 'abc' );

		$this->assertEquals(
			[ '[2,"abc"]' ],
			$instance->getErrors()
		);

		$instance->addErrorWithMsgKey( 'Foo' );

		$this->assertCount(
			2,
			$instance->getErrors()
		);
	}

	public function testDescriptionForPropertyObjectValue() {

		$instance = new DescriptionProcessor();

		$this->assertInstanceOf(
			'SMW\Query\Language\Description',
			$instance->newDescriptionForPropertyObjectValue( new DIProperty( 'Foo' ), 'bar' )
		);
	}

	public function testDescriptionForWikiPageValueChunkOnEqualMatch() {

		$instance = new DescriptionProcessor();

		$valueDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$valueDescription
		);

		$this->assertEquals(
			new DIWikiPage( 'Bar', NS_MAIN ),
			$valueDescription->getDataItem()
		);
	}

	public function testnewDescriptionForWikiPageValueChunkOnApproximateValue() {

		$instance = new DescriptionProcessor();

		$valueDescription = $instance->newDescriptionForWikiPageValueChunk( '~bar' );

		$this->assertEquals(
			new DIWikiPage( 'bar', NS_MAIN ),
			$valueDescription->getDataItem()
		);
	}

	public function testGetDisjunctiveCompoundDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->asOr( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentConjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->asOr( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentDisjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->asOr( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullNewDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->asOr( $currentDescription, null )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullCurrentDescription() {

		$instance = new DescriptionProcessor();

		$newDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->asOr( null, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentConjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentDisjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullNewDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->asAnd( $currentDescription, null )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullCurrentDescription() {

		$instance = new DescriptionProcessor();

		$newDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->asAnd( null, $newDescription )
		);
	}

	public function testConstuctDescriptionWithContextPage() {

		$instance = new DescriptionProcessor();

		$instance->setContextPage(
			DIWikiPage::newFromText( __METHOD__ )
		);

		$currentDescription = new Disjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

}
