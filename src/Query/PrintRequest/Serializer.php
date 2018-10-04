<?php

namespace SMW\Query\PrintRequest;

use SMW\Localizer;
use SMW\Query\PrintRequest;

/**
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author Markus Krötzsch
 * @author mwjames
 */
class Serializer {

	/**
	 * @since 2.5
	 *
	 * @param PrintRequest $printRequest
	 * @param boolean $showparams that sets if the serialization should include
	 * the extra print request parameters
	 *
	 * @return string
	 */
	public static function serialize( PrintRequest $printRequest, $showparams = false ) {
		$parameters = '';

		if ( $showparams ) {

			// #2037 index is required as helper parameter during the result
			// display but is not part of the original request
			if ( $printRequest->getParameter( 'lang' ) ) {
				$printRequest->removeParameter( 'index' );
			};

			foreach ( $printRequest->getParameters() as $key => $value ) {
				$parameters .= "|+" . $key . "=" . $value;
			}
		}

		switch ( $printRequest->getMode() ) {
			case PrintRequest::PRINT_CATS:
				return self::doSerializeCat( $printRequest, $parameters );
			case PrintRequest::PRINT_CCAT:
				return self::doSerializeCcat( $printRequest, $parameters );
			case PrintRequest::PRINT_CHAIN:
			case PrintRequest::PRINT_PROP:
				return self::doSerializeProp( $printRequest, $parameters );
			case PrintRequest::PRINT_THIS:
				return self::doSerializeThis( $printRequest, $parameters );
			default:
				return '';
		}

		return ''; // no current serialisation
	}

	private static function doSerializeCat( $printRequest, $parameters ) {

		$catlabel = Localizer::getInstance()->getNamespaceTextById( NS_CATEGORY );
		$result = '?' . $catlabel;

		if ( $printRequest->getLabel() != $catlabel ) {
			$result .= '=' . $printRequest->getLabel();
		}

		return $result . $parameters;
	}

	private static function doSerializeCcat( $printRequest, $parameters ) {

		$printname = $printRequest->getData()->getPrefixedText();
		$result = '?' . $printname;

		if ( $printRequest->getOutputFormat() != 'x' ) {
			$result .= '#' . $printRequest->getOutputFormat();
		}

		if ( $printRequest->getLabel() != $printname ) {
			$result .= '=' . $printRequest->getLabel();
		}

		return $result . $parameters;
	}

	private static function doSerializeProp( $printRequest, $parameters ) {

		$printname = '';

		$label = $printRequest->getLabel();
		$data = $printRequest->getData();

		if ( $data->isVisible() ) {
			// #1564
			// Use the canonical form for predefined properties to ensure
			// that local representations are for display but points to
			// the correct property
			if ( $printRequest->isMode( PrintRequest::PRINT_CHAIN ) ) {
				$printname = $data->getDataItem()->getString();
				// If the preferred label and invoked label are the same
				// then no additional label is required as the label is
				// recognized as being available by the system
				if ( $label === $data->getLastPropertyChainValue()->getDataItem()->getPreferredLabel() ) {
					$label = $printname;
				}
			} else {

				$printname = $data->getDataItem()->getCanonicalLabel();

				if ( $label === $data->getDataItem()->getPreferredLabel() ) {
					$label = $printname;
				}

				// Don't carry a localized label for a predefined property
				// (fetched via the wikiValue)
				if ( !$data->getDataItem()->isUserDefined() && $label === $data->getWikiValue() ) {
					$label = $data->getDataItem()->getCanonicalLabel();
				}
			}
		}

		$result = '?' . $printname;

		if ( $printRequest->getOutputFormat() !== '' ) {
			$result .= '#' . $printRequest->getOutputFormat();
		}

		if ( $printname != $label && $label !== '' ) {
			$result .= '=' . $label;
		}

		return $result . $parameters;
	}

	private static function doSerializeThis( $printRequest, $parameters ) {

		$result = '?';

		// Has leading ?#
		if ( $printRequest->hasLabelMarker() ) {
			$result .= '#';
		}

		if ( $printRequest->getLabel() !== '' ) {
			$result .= '=' . $printRequest->getLabel();
		}

		$outputFormat = $printRequest->getOutputFormat();

		if ( $outputFormat !== '' && $outputFormat !== false && $outputFormat !== null ) {

			// Handle ?, ?#- vs. ?#Foo=#-
			if ( $printRequest->getLabel() !== '' ) {
				$result .= '#';
			}

			$result .= $outputFormat;
		}

		return $result . $parameters;
	}

}
