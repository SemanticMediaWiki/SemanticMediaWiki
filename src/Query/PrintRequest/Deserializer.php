<?php

namespace SMW\Query\PrintRequest;

use InvalidArgumentException;
use SMW\DataValueFactory;
use SMW\DataValues\PropertyChainValue;
use SMW\DataValues\PropertyValue;
use SMW\Localizer;
use SMW\Query\PrintRequest;
use Title;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class Deserializer {

	/**
	 * Create an PrintRequest object from a string description as one
	 * would normally use in #ask and related inputs. The string must start
	 * with a "?" and may contain label and formatting parameters after "="
	 * or "#", respectively. However, further parameters, given in #ask by
	 * "|+param=value" are not allowed here; they must be added
	 * individually.
	 *
	 * @since 2.5
	 *
	 * @param string $text
	 * @param array $options
	 *
	 * @return PrintRequest|null
	 */
	public static function deserialize( $text, array $options = [] ) {
		$showMode = false;
		$useCanonicalLabel = false;

		if ( isset( $options['show_mode'] ) ) {
			$showMode = $options['show_mode'];
		}

		if ( isset( $options['canonical_label'] ) ) {
			$useCanonicalLabel = $options['canonical_label'];
		}

		list( $parts, $outputFormat, $printRequestLabel ) = self::getPartsFromText(
			$text
		);

		// default
		$label = '';
		$data = null;

		if ( $printRequestLabel === '' ) { // print "this"
			$printmode = PrintRequest::PRINT_THIS;

			// Distinguish the case of an empty format
			if ( $outputFormat === '' ) {
				$outputFormat = null;
			}

		} elseif ( self::isCategory( $printRequestLabel ) ) { // print categories
			$printmode = PrintRequest::PRINT_CATS;

			if ( $showMode === false ) {
				$label = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
			}
		} elseif ( PropertyChainValue::isChained( $printRequestLabel ) ) {
			$printmode = PrintRequest::PRINT_CHAIN;

			$data = DataValueFactory::getInstance()->newDataValueByType(
				PropertyChainValue::TYPE_ID
			);

			$data->setOption( PropertyValue::OPT_CANONICAL_LABEL, $useCanonicalLabel );
			$data->setUserValue( $printRequestLabel );

			if ( $showMode === false ) {
				$label = $data->getLastPropertyChainValue()->getWikiValue();
			}
		} else { // print property or check category
			$title = Title::newFromText( $printRequestLabel, SMW_NS_PROPERTY );

			// not a legal property/category name; give up
			if ( $title === null ) {
				return null;
			}

			if ( $title->getNamespace() == NS_CATEGORY ) {
				$printmode = PrintRequest::PRINT_CCAT;
				$data = $title;

				if ( $showMode === false ) {
					$label = $title->getText();
				}
			} else {
				$printmode = PrintRequest::PRINT_PROP;

				// Enforce interpretation as property (even if it starts with
				// something that looks like another namespace)
				$data = DataValueFactory::getInstance()->newPropertyValueByLabel(
					$printRequestLabel
				);

				$data->setOption( PropertyValue::OPT_CANONICAL_LABEL, $useCanonicalLabel );

				if ( !$data->isValid() ) { // not a property; give up
					return null;
				}

				if ( $showMode === false ) {
					$label = $data->getWikiValue();
				}
			}
		}

		// "plain printout"
		// @docu mentions that `?foo#` is equal to `?foo#-` and avoid an
		// empty string to distinguish it from "false"
		if ( $outputFormat === '' ) {
			$outputFormat = '-';
		}

		// label found, use this instead of default
		if ( count( $parts ) > 1 ) {
			$label = trim( $parts[1] );
		}

		if ( $printmode === PrintRequest::PRINT_THIS ) {

			// Cover the case of `?#Test=#-`
			if ( strrpos( $label, '#' ) !== false ) {
				list( $label, $outputFormat ) = explode( '#', $label );

				// `?#=foo#` is equal to `?#=foo#-`
				if ( $outputFormat === '' ) {
					$outputFormat = '-';
				}
			}
		}

		try {
			$printRequest = new PrintRequest( $printmode, $label, $data, trim( $outputFormat ) );
			$printRequest->markThisLabel( $text );
		} catch ( InvalidArgumentException $e ) {
			// something still went wrong; give up
			$printRequest = null;
		}

		return $printRequest;
	}

	private static function isCategory( $text ) {

		$text = mb_convert_case( $text, MB_CASE_TITLE );

		// Check for the canonical form (singular, plural)
		if ( $text == 'Category' || $text == 'Categories' ) {
			return true;
		}

		return Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY ) == $text;
	}

	private static function getPartsFromText( $text ) {

		// #1464
		// Temporary encode "=" within a <> entity (<span>...</span>)
		$text = preg_replace_callback( "/(<(.*?)>(.*?)>)/u", function( $matches ) {
			foreach ( $matches as $match ) {
				return str_replace( [ '=' ], [ '-3D' ], $match );
			}
		}, $text );

		$parts = explode( '=', $text, 2 );

		// Restore temporary encoding
		$parts[0] = str_replace( [ '-3D' ], [ '=' ], $parts[0] );

		if ( isset( $parts[1] ) ) {
			$parts[1] = str_replace( [ '-3D' ], [ '=' ], $parts[1] );
		}

		$propparts = explode( '#', $parts[0], 2 );
		$printRequestLabel = trim( $propparts[0] );
		$outputFormat = isset( $propparts[1] ) ? trim( $propparts[1] ) : false;

		return [ $parts, $outputFormat, smwfNormalTitleText( $printRequestLabel ) ];
	}

}
