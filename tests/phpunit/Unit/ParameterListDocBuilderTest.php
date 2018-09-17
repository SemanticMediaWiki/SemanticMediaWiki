<?php

namespace SMW\Test;

use ParamProcessor\ParamDefinition;
use SMW\ParameterListDocBuilder;
use SMW\Tests\Utils\UtilityFactory;
use SMW\Tests\Utils\Validators\StringValidator;

/**
 * @covers SMW\ParameterListDocBuilder
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ParameterListDocBuilderTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @var ParameterListDocBuilder
	 */
	private $builder;

	/**
	 * @var StringValidator
	 */
	private $stringValidator;

	protected function setUp() {
		parent::setUp();

		$this->stringValidator = UtilityFactory::getInstance()->newValidatorFactory()->newStringValidator();

		$this->builder = new ParameterListDocBuilder( function( $key ) {
			return $key;
		} );
	}

	public function testGivenNoParameters_noTableIsReturned() {
		$wikiText = $this->builder->getParameterTable( [] );

		$this->assertSame(
			'',
			$wikiText
		);
	}

	public function testGivenMinimalParameter_defaultIsRequired() {
		$wikiText = $this->builder->getParameterTable( [
			new ParamDefinition( 'number', 'length' )
		] );

		$expected = [
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
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

	public function testGivenParameterWithDefault_defaultIsListed() {
		$wikiText = $this->builder->getParameterTable( [
			new ParamDefinition( 'number', 'length', 42 )
		] );

		$expected = [
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
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

	public function testGivenParameterWithAliases_aliasesAreListed() {
		$paramWithAliases = new ParamDefinition( 'number', 'param-with-alias' );
		$paramWithAliases->addAliases( 'first-alias', 'second-alias' );

		$paramWithoutAliases = new ParamDefinition( 'string', 'no-aliases' );

		$wikiText = $this->builder->getParameterTable( [ $paramWithAliases, $paramWithoutAliases ] );

		$expected = [
			'{| class="wikitable sortable"',
			'!validator-describe-header-parameter',
			'!validator-describe-header-type',
			'!validator-describe-header-default',
			'!validator-describe-header-description',
			'|-',
			'|param-with-alias',
			'|first-alias, second-alias',
			'|validator-type-number',
			"|''validator-describe-required''",
			'|',
			'|-',
			'|no-aliases',
			'| -',
			'|validator-type-string',
			"|''validator-describe-required''",
			'|',
			'|}'
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

	public function testGivenBooleanParameter_defaultIsListedAsString() {
		$wikiText = $this->builder->getParameterTable( [
			new ParamDefinition( 'boolean', 'awesome', true )
		] );

		$expected = [
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
		];

		$this->stringValidator->assertThatStringContains(
			$expected,
			$wikiText
		);
	}

}
