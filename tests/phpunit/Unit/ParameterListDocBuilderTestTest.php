<?php

namespace SMW\Test;

use ParamProcessor\ParamDefinition;
use SMW\ParameterListDocBuilder;
use SMW\Tests\Utils\UtilityFactory;

/**
 * @covers SMW\ParameterListDocBuilder
 * @group semanitc-mediawiki
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ParameterListDocBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ParameterListDocBuilder
	 */
	private $builder;

	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		$this->builder = new ParameterListDocBuilder( function( $key ) {
			return $key;
		} );
	}

	public function testGivenNoParameters_noTableIsReturned() {
		$wikiText = $this->builder->getParameterTable( array() );

		$this->assertSame(
			'',
			$wikiText
		);
	}

	public function testGivenMinimalParameter_defaultIsRequired() {
		$wikiText = $this->builder->getParameterTable( array(
			new ParamDefinition( 'number', 'length' )
		) );

		$expected = array(
			'{| class="wikitable sortable"',
			'!validator-describe-header-parameter',
			'!validator-describe-header-type',
			'!validator-describe-header-default',
			'!validator-describe-header-description',
			'|-',
			'|length',
			'|validator-type-number',
			"|''validator-describe-required''",
			'|',
			'|}'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

	public function testGivenParameterWithDefault_defaultIsListed() {
		$wikiText = $this->builder->getParameterTable( array(
			new ParamDefinition( 'number', 'length', 42 )
		) );

		$expected = array(
			'{| class="wikitable sortable"',
			'!validator-describe-header-parameter',
			'!validator-describe-header-type',
			'!validator-describe-header-default',
			'!validator-describe-header-description',
			'|-',
			'|length',
			'|validator-type-number',
			"|42",
			'|',
			'|}'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

	public function testGivenParameterWithAliases_aliasesAreListed() {
		$param = new ParamDefinition( 'number', 'length' );
		$param->addAliases( 'abc', 'def' );

		$wikiText = $this->builder->getParameterTable( array( $param ) );

		$expected = array(
			'{| class="wikitable sortable"',
			'!validator-describe-header-parameter',
			'!validator-describe-header-type',
			'!validator-describe-header-default',
			'!validator-describe-header-description',
			'|-',
			'|length',
			'|abc, def',
			'|validator-type-number',
			"|''validator-describe-required''",
			'|',
			'|}'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

	public function testGivenBooleanParameter_defaultIsListedAsString() {
		$wikiText = $this->builder->getParameterTable( array(
			new ParamDefinition( 'boolean', 'awesome', true )
		) );

		$expected = array(
			'{| class="wikitable sortable"',
			'!validator-describe-header-parameter',
			'!validator-describe-header-type',
			'!validator-describe-header-default',
			'!validator-describe-header-description',
			'|-',
			'|awesome',
			'|validator-type-boolean',
			"|yes",
			'|',
			'|}'
		);

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

}
