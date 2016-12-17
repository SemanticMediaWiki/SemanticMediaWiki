<?php

namespace SMW\Tests\MediaWiki\Specials\Ask;

use SMW\MediaWiki\Specials\Ask\ParametersFormWidget;

/**
 * @covers \SMW\MediaWiki\Specials\Ask\ParametersFormWidget
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class ParametersFormWidgetTest extends \PHPUnit_Framework_TestCase {

	public function testCanConstruct() {

		$this->assertInstanceOf(
			'\SMW\MediaWiki\Specials\Ask\ParametersFormWidget',
			new ParametersFormWidget()
		);
	}

	/**
	 * @dataProvider parametersProvider
	 */
	public function testCreateParametersForm( $format, $parameters, $expected ) {

		$instance = new ParametersFormWidget();

		$this->assertContains(
			$expected,
			$instance->createParametersForm( $format, $parameters )
		);
	}

	public function parametersProvider() {

		$provider[] = array(
			'',
			array(),
			'<table class="smw-ask-otheroptions" width="100%"><tbody><tr style="background: #eee"></tr></tbody></table>'
		);

		$provider[] = array(
			'table',
			array(),
			'<table class="smw-ask-otheroptions"'
		);

		return $provider;
	}

}
