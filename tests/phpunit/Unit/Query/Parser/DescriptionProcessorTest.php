<?php

namespace SMW\Tests\Query\Parser;

use SMW\DIProperty;
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

	public function testGetDescriptionForPropertyObjectValue() {

		$instance = new DescriptionProcessor();

		$this->assertInstanceOf(
			'SMW\Query\Language\Description',
			$instance->getDescriptionForPropertyObjectValue( new DIProperty( 'Foo' ), 'bar' )
		);
	}

	public function testGetDescriptionForWikiPageValueChunk() {

		$instance = new DescriptionProcessor();

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->getDescriptionForWikiPageValueChunk( 'bar' )
		);
	}

	public function testGetDisjunctiveCompoundDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->getDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->getDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->getDisjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentConjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->getDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->getDisjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentDisjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->getDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Disjunction',
			$instance->getDisjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullNewDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->getDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->getDisjunctiveCompoundDescriptionFrom( $currentDescription, null )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullCurrentDescription() {

		$instance = new DescriptionProcessor();

		$newDescription = $instance->getDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->getDisjunctiveCompoundDescriptionFrom( null, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->getDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->getDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->getConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentConjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->getDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->getConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentDisjunction() {

		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->getDescriptionForPropertyObjectValue(
			new DIProperty( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			'SMW\Query\Language\Conjunction',
			$instance->getConjunctiveCompoundDescriptionFrom( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullNewDescription() {

		$instance = new DescriptionProcessor();

		$currentDescription = $instance->getDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->getConjunctiveCompoundDescriptionFrom( $currentDescription, null )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullCurrentDescription() {

		$instance = new DescriptionProcessor();

		$newDescription = $instance->getDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			'SMW\Query\Language\ValueDescription',
			$instance->getConjunctiveCompoundDescriptionFrom( null, $newDescription )
		);
	}

}
