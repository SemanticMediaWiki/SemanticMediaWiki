<?php

namespace SMW\Tests\Query\Parser;

use PHPUnit\Framework\TestCase;
use SMW\DataItems\Property;
use SMW\DataItems\WikiPage;
use SMW\Query\Language\Conjunction;
use SMW\Query\Language\Description;
use SMW\Query\Language\Disjunction;
use SMW\Query\Language\ValueDescription;
use SMW\Query\Parser\DescriptionProcessor;

/**
 * @covers SMW\Query\Parser\DescriptionProcessor
 * @group semantic-mediawiki
 *
 * @license GPL-2.0-or-later
 * @since 2.4
 *
 * @author mwjames
 */
class DescriptionProcessorTest extends TestCase {

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
			Description::class,
			$instance->newDescriptionForPropertyObjectValue( new Property( 'Foo' ), 'bar' )
		);
	}

	public function testDescriptionForWikiPageValueChunkOnEqualMatch() {
		$instance = new DescriptionProcessor();

		$valueDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			ValueDescription::class,
			$valueDescription
		);

		$this->assertEquals(
			new WikiPage( 'Bar', NS_MAIN ),
			$valueDescription->getDataItem()
		);
	}

	public function testnewDescriptionForWikiPageValueChunkOnApproximateValue() {
		$instance = new DescriptionProcessor();

		$valueDescription = $instance->newDescriptionForWikiPageValueChunk( '~bar' );

		$this->assertEquals(
			new WikiPage( 'bar', NS_MAIN ),
			$valueDescription->getDataItem()
		);
	}

	public function testGetDisjunctiveCompoundDescription() {
		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Disjunction::class,
			$instance->asOr( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentConjunction() {
		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Disjunction::class,
			$instance->asOr( $currentDescription, $newDescription )
		);
	}

	public function testGetDisjunctiveCompoundDescriptionForCurrentDisjunction() {
		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Disjunction::class,
			$instance->asOr( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullNewDescription() {
		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			ValueDescription::class,
			$instance->asOr( $currentDescription, null )
		);
	}

	public function testTryToGetDisjunctiveCompoundDescriptionForNullCurrentDescription() {
		$instance = new DescriptionProcessor();

		$newDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			ValueDescription::class,
			$instance->asOr( null, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescription() {
		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentConjunction() {
		$instance = new DescriptionProcessor();

		$currentDescription = new Conjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

	public function testGetConjunctiveCompoundDescriptionForCurrentDisjunction() {
		$instance = new DescriptionProcessor();

		$currentDescription = new Disjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullNewDescription() {
		$instance = new DescriptionProcessor();

		$currentDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			ValueDescription::class,
			$instance->asAnd( $currentDescription, null )
		);
	}

	public function testTryToGetConjunctiveCompoundDescriptionForNullCurrentDescription() {
		$instance = new DescriptionProcessor();

		$newDescription = $instance->newDescriptionForWikiPageValueChunk( 'bar' );

		$this->assertInstanceOf(
			ValueDescription::class,
			$instance->asAnd( null, $newDescription )
		);
	}

	public function testConstuctDescriptionWithContextPage() {
		$instance = new DescriptionProcessor();

		$instance->setContextPage(
			WikiPage::newFromText( __METHOD__ )
		);

		$currentDescription = new Disjunction();

		$newDescription = $instance->newDescriptionForPropertyObjectValue(
			new Property( 'Foo' ),
			'foobar'
		);

		$this->assertInstanceOf(
			Conjunction::class,
			$instance->asAnd( $currentDescription, $newDescription )
		);
	}

}
