<?php

namespace SMW\Tests\Integration;

use SMW\DIProperty;
use SMW\PropertyRegistry;

/**
 * @group semantic-mediawiki
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertyLabelCanonicalMatchTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider canonicalLabelProvider
	 */
	public function testFindPropertyIdByLabel( $label, $expectedKey, $expectedLabel ) {

		list( $labelMatch, $property ) = $this->findPropertyIdByLabel( $label );

		$this->assertEquals(
			$labelMatch,
			$expectedLabel
		);

		$this->assertEquals(
			$expectedKey,
			$property->getKey()
		);
	}

	/**
	 * @dataProvider canonicalLabelProvider
	 */
	public function testNewFromUserLabel( $label, $expectedKey, $expectedLabel ) {

		list( $labelMatch, $property ) = $this->newFromUserLabel( $label );

		$this->assertEquals(
			$labelMatch,
			$expectedLabel
		);

		$this->assertEquals(
			$expectedKey,
			$property->getKey()
		);
	}

	/**
	 * @dataProvider canonicalLabelWithLanguageProvider
	 */
	public function testNewFromUserLabelWithLanguage( $label, $languageCode, $expectedKey, $expectedLabel ) {

		list( $labelMatch, $property ) = $this->newFromUserLabel( $label, $languageCode );

		$this->assertEquals(
			$labelMatch,
			$expectedLabel
		);

		$this->assertEquals(
			$expectedKey,
			$property->getKey()
		);
	}

	private function findPropertyIdByLabel( $label ) {

		$property = new DIProperty(
			PropertyRegistry::getInstance()->findPropertyIdByLabel( $label )
		);

		$canonicalLabel = $property->getCanonicalLabel();

		// #1966 and #1968
		// In case something goes wrong, a recursive loop will kill PHP
		// and we know we messed up
		if ( $canonicalLabel !== '' && $label !== $canonicalLabel ) {
			$this->findPropertyIdByLabel(
				$property->getCanonicalDiWikiPage()->getTitle()->getText()
			);
		}

		return array( $label, $property );
	}

	private function newFromUserLabel( $label, $languageCode = false ) {

		$property = DIProperty::newFromUserLabel( $label, false, $languageCode );

		$canonicalLabel = $property->getCanonicalLabel();

		// #1966 and #1968
		// In case something goes wrong, a recursive loop will kill PHP
		// and we know we messed up
		if ( $canonicalLabel !== '' && $label !== $canonicalLabel ) {
			$this->newFromUserLabel(
				$property->getCanonicalDiWikiPage()->getTitle()->getText()
			);
		}

		return array( $label, $property );
	}

	public function canonicalLabelProvider() {

		$provider[] = array(
			'Number',
			'_num',
			'Number',
		);

		$provider[] = array(
			'Float',
			'_num',
			'Float'
		);

		$provider[] = array(
			'Telephone number',
			'_tel',
			'Telephone number'
		);

		$provider[] = array(
			'Phone number',
			'_tel',
			'Phone number'
		);

		return $provider;
	}

	public function canonicalLabelWithLanguageProvider() {

		$provider[] = array(
			'Number',
			'en',
			'_num',
			'Number'
		);

		$provider[] = array(
			'Number',
			'fr',
			'_num',
			'Number'
		);

		$provider[] = array(
			'Booléen',
			'fr',
			'_boo',
			'Booléen'
		);

		return $provider;
	}

}
