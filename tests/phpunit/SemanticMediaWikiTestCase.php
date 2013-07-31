<?php

namespace SMW\Test;

use SMW\DataValueFactory;
use SMW\ArrayAccessor;
use SMW\SemanticData;
use SMW\DIWikiPage;
use SMW\Settings;

use RequestContext;
use FauxRequest;
use WebRequest;
use Language;
use Title;

use ReflectionClass;

use SMWDataItem;

/**
 * Class contains general purpose methods
 *
 * @file
 *
 * @license GNU GPL v2+
 * @since   1.9
 *
 * @author mwjames
 */

/**
 * Class contains general purpose methods
 *
 * @ingroup Test
 *
 * @group SMW
 * @group SMWExtension
 */
abstract class SemanticMediaWikiTestCase extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns the name of the deriving class being tested
	 *
	 * @since 1.9
	 *
	 * @return string
	 */
	public abstract function getClass();

	/**
	 * Helper method that returns a MockObjectBuilder object
	 *
	 * @since 1.9
	 *
	 * @param array $accessor
	 *
	 * @return MockObjectBuilder
	 */
	public function newMockObject( array $accessor = array() ) {
		return new MockObjectBuilder( new ArrayAccessor( $accessor ) );
	}

	/**
	 * Helper method that returns a ReflectionClass object
	 *
	 * @since 1.9
	 *
	 * @param string|null $class
	 *
	 * @return ReflectionClass
	 */
	public function newReflector( $class = null ) {
		return new ReflectionClass( $class === null ? $this->getClass() : $class );
	}

	/**
	 * Helper method that returns a randomized Title object to avoid results
	 * are influenced by cross instantiated objects with the same title name
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 *
	 * @return Title
	 */
	protected function getTitle( $namespace = NS_MAIN ) {
		return $this->newTitle( $namespace );
	}

	/**
	 * Helper method that returns a randomized Title object to avoid results
	 * are influenced by cross instantiated objects with the same title name
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 * @param $text|null
	 *
	 * @return Title
	 */
	protected function newTitle( $namespace = NS_MAIN, $text = null ) {
		return Title::newFromText( $text === null ? $this->getRandomString() : $text, $namespace );
	}

	/**
	 * Helper method that returns a User object
	 *
	 * @since 1.9
	 *
	 * @return User
	 */
	protected function getUser() {
		return new MockSuperUser();
	}

	/**
	 * Helper method that returns a Language object
	 *
	 * @since 1.9
	 *
	 * @return Language
	 */
	protected function getLanguage( $langCode = 'en' ) {
		return Language::factory( $langCode );
	}

	/**
	 * Returns RequestContext object
	 *
	 * @param array $params
	 *
	 * @return RequestContext
	 */
	protected function newContext( $request = array() ) {

		$context = new RequestContext();

		if ( $request instanceof WebRequest ) {
			$context->setRequest( $request );
		} else {
			$context->setRequest( new FauxRequest( $request, true ) );
		}

		$context->setUser( new MockSuperUser() );

		return $context;
	}

	/**
	 * Helper method that returns a randomized DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @param $namespace
	 *
	 * @return DIWikiPage
	 */
	protected function getSubject( $namespace = NS_MAIN ) {
		return DIWikiPage::newFromTitle( $this->getTitle( $namespace ) );
	}

	/**
	 * Helper method that returns a DIWikiPage object
	 *
	 * @since 1.9
	 *
	 * @param Title|null $title
	 *
	 * @return DIWikiPage
	 */
	protected function newSubject( Title $title = null ) {
		return DIWikiPage::newFromTitle( $title === null ? $this->getTitle() : $title );
	}

	/**
	 * Helper method that returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 *
	 * @return Settings
	 */
	protected function getSettings( array $settings = array() ) {
		return $this->newSettings( $settings );
	}

	/**
	 * Helper method that returns a Settings object
	 *
	 * @since 1.9
	 *
	 * @param array $settings
	 *
	 * @return Settings
	 */
	protected function newSettings( array $settings = array() ) {
		return Settings::newFromArray( $settings );
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 *
	 * @return string
	 */
	protected function getRandomString( $length = 10, $prefix = null ) {
		return $this->newRandomString( $length, $prefix );
	}

	/**
	 * Helper method that returns a random string
	 *
	 * @since 1.9
	 *
	 * @param $length
	 * @param $prefix identify a specific random string during testing
	 *
	 * @return string
	 */
	protected function newRandomString( $length = 10, $prefix = null ) {
		return $prefix . ( $prefix ? '-' : '' ) . substr( str_shuffle( "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ" ), 0, $length );
	}

	/**
	 * Utility method taking an array of elements and wrapping
	 * each element in it's own array. Useful for data providers
	 * that only return a single argument.
	 *
	 * @see MediaWikiTestCase::arrayWrap
	 *
	 * @since 1.9
	 *
	 * @param array $elements
	 *
	 * @return array
	 */
	protected function arrayWrap( array $elements ) {
		return array_map(
			function ( $element ) {
				return array( $element );
			},
			$elements
		);
	}

	/**
	 * Asserts that for a given semantic container expected property / value
	 * pairs are available
	 *
	 * Expected assertion array should follow
	 * 'propertyCount' => int
	 * 'propertyLabel' => array() or 'propertyKey' => array()
	 * 'propertyValue' => array()
	 *
	 * @param SemanticData $semanticData
	 * @param array $expected
	 */
	protected function assertSemanticData( SemanticData $semanticData, array $expected ) {
		$this->assertCount( $expected['propertyCount'], $semanticData->getProperties() );

		// Assert expected properties
		foreach ( $semanticData->getProperties() as $key => $diproperty ) {
			$this->assertInstanceOf( '\SMW\DIProperty', $diproperty );

			if ( isset( $expected['propertyKey']) ){
				$this->assertContains( $diproperty->getKey(), $expected['propertyKey'] );
			} else {
				$this->assertContains( $diproperty->getLabel(), $expected['propertyLabel'] );
			}

			// Assert property values
			foreach ( $semanticData->getPropertyValues( $diproperty ) as $dataItem ){
				$dataValue = DataValueFactory::newDataItemValue( $dataItem, $diproperty );
				$DItype = $dataValue->getDataItem()->getDIType();

				if ( $DItype === SMWDataItem::TYPE_WIKIPAGE ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['propertyValue'] );
				} else if ( $DItype === SMWDataItem::TYPE_NUMBER ){
					$this->assertContains( $dataValue->getNumber(), $expected['propertyValue'] );
				} else if ( $DItype === SMWDataItem::TYPE_TIME ){
					$this->assertContains( $dataValue->getISO8601Date(), $expected['propertyValue'] );
				} else if ( $DItype === SMWDataItem::TYPE_BLOB ){
					$this->assertContains( $dataValue->getWikiValue(), $expected['propertyValue'] );
				}

			}
		}
	}
}
