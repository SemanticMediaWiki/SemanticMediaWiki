<?php

/**
 * Functions for handling Semantic MediaWiki data within the Page Schemas
 * extension.
 *
 * @author Ankit Garg
 * @author Yaron Koren
 * @ingroup SMW
 */

class SMWPageSchemas extends PSExtensionHandler {

	public static function getDisplayColor() {
		return '#DEF';
	}

	public static function getTemplateDisplayString() {
		return 'Connecting property';
	}

	public static function getFieldDisplayString() {
		return 'Semantic property';
	}

	/**
	 * Returns the display info for the "connecting property" (if any)
	 * of the #subobject call (if any) in this template.
	 */
	public static function getTemplateDisplayValues( $templateXML ) {
		foreach ( $templateXML->children() as $tag => $child ) {
			if ( $tag == "semanticmediawiki_ConnectingProperty" ) {
				$propName = $child->attributes()->name;
				$values = array();
				return array( $propName, $values );
			}
		}
		return null;
	}

	/**
	 * Returns the display info for the property (if any is defined)
	 * for a single field in the Page Schemas XML.
	 */
	public static function getFieldDisplayValues( $fieldXML ) {
		foreach ( $fieldXML->children() as $tag => $child ) {
			if ( $tag == "semanticmediawiki_Property" ) {
				$propName = $child->attributes()->name;
				$values = array();
				foreach ( $child->children() as $prop => $value ) {
					$values[$prop] = (string)$value;
				}
				return array( $propName, $values );
			}
		}
		return null;
	}

	/**
	 * Returns the set of SMW property data from the entire page schema.
	 */
	static function getAllPropertyData( $pageSchemaObj ) {
		$propertyDataArray = array();
		$psTemplates = $pageSchemaObj->getTemplates();
		foreach ( $psTemplates as $psTemplate ) {
			$psTemplateFields = $psTemplate->getFields();
			foreach ( $psTemplateFields as $psTemplateField ) {
				$prop_array = $psTemplateField->getObject('semanticmediawiki_Property');
				if ( empty( $prop_array ) ) {
					continue;
				}
				// If property name is blank, set it to the
				// field name.
				if ( !array_key_exists( 'name', $prop_array ) || empty( $prop_array['name'] ) ) {
					$prop_array['name'] = $psTemplateField->getName();
				}
				$propertyDataArray[] = $prop_array;
			}
		}
		return $propertyDataArray;
	}

	/**
	 * Constructs XML for the "connecting property", based on what was
	 * submitted in the 'edit schema' form.
	 */
	public static function createTemplateXMLFromForm() {
		global $wgRequest;

		$xmlPerTemplate = array();
		foreach ( $wgRequest->getValues() as $var => $val ) {
			if ( substr( $var, 0, 24 ) == 'smw_connecting_property_' ) {
				$templateNum = substr( $var, 24 );
				$xml = '<semanticmediawiki_ConnectingProperty name="' . $val . '" />';
				$xmlPerTemplate[$templateNum] = $xml;
			}
		}
		return $xmlPerTemplate;
	}

	static function getConnectingPropertyName( $psTemplate ) {
		// TODO - there should be a more direct way to get
		// this data.
		$smwConnectingPropertyArray = $psTemplate->getObject( 'semanticmediawiki_ConnectingProperty' );
		return PageSchemas::getValueFromObject( $smwConnectingPropertyArray, 'name' );
	}

	/**
	 * Sets the list of property pages defined by the passed-in
	 * Page Schemas object.
	 */
	public static function getPagesToGenerate( $pageSchemaObj ) {
		$pagesToGenerate = array();

		$psTemplates = $pageSchemaObj->getTemplates();
		foreach ( $psTemplates as $psTemplate ) {
			$smwConnectingPropertyName = self::getConnectingPropertyName( $psTemplate );
			if ( is_null( $smwConnectingPropertyName ) ) {
				continue;
			}
			$pagesToGenerate[] = Title::makeTitleSafe( SMW_NS_PROPERTY, $smwConnectingPropertyName );
		}

		$propertyDataArray = self::getAllPropertyData( $pageSchemaObj );
		foreach ( $propertyDataArray as $propertyData ) {
			$title = Title::makeTitleSafe( SMW_NS_PROPERTY, $propertyData['name'] );
			$pagesToGenerate[] = $title;
		}
		return $pagesToGenerate;
	}

	/**
	 * Constructs XML for the SMW property, based on what was submitted
	 * in the 'edit schema' form.
	 */
	public static function createFieldXMLFromForm() {
		global $wgRequest;

		$fieldNum = -1;
		$xmlPerField = array();
		foreach ( $wgRequest->getValues() as $var => $val ) {
			if ( substr( $var, 0, 18 ) == 'smw_property_name_' ) {
				$fieldNum = substr( $var, 18 );
				$xml = '<semanticmediawiki_Property name="' . $val . '" >';
			} elseif ( substr( $var, 0, 18 ) == 'smw_property_type_'){
				$xml .= '<Type>' . $val . '</Type>';
			} elseif ( substr( $var, 0, 16 ) == 'smw_linked_form_') {
				if ( $val !== '' ) {
					$xml .= '<LinkedForm>' . $val . '</LinkedForm>';
				}
			} elseif ( substr( $var, 0, 11 ) == 'smw_values_') {
				if ( $val !== '' ) {
					// replace the comma substitution character that has no chance of
					// being included in the values list - namely, the ASCII beep
					$listSeparator = ',';
					$allowed_values_str = str_replace( "\\$listSeparator", "\a", $val );
					$allowed_values_array = explode( $listSeparator, $allowed_values_str );
					foreach ( $allowed_values_array as $value ) {
						// replace beep back with comma, trim
						$value = str_replace( "\a", $listSeparator, trim( $value ) );
						$xml .= '<AllowedValue>' . $value . '</AllowedValue>';
					}
				}
				$xml .= '</semanticmediawiki_Property>';
				$xmlPerField[$fieldNum] = $xml;
			}
		}
		return $xmlPerField;
	}

	/**
	 * Returns the HTML necessary for getting information about the
	 * "connecting property" within the Page Schemas 'editschema' page.
	 */
	public static function getTemplateEditingHTML( $psTemplate) {
		// Only display this if the Semantic Internal Objects extension
		// isn't displaying something similar.
		if ( class_exists( 'SIOPageSchemas' ) ) {
			return null;
		}

		$prop_array = array();
		$hasExistingValues = false;
		if ( !is_null( $psTemplate ) ) {
			$prop_array = $psTemplate->getObject( 'semanticmediawiki_ConnectingProperty' );
			if ( !is_null( $prop_array ) ) {
				$hasExistingValues = true;
			}
		}
		$text = '<p>' . 'Name of property to connect this template\'s fields to the rest of the page:' . ' ' . '(should only be used if this template can have multiple instances)' . ' ';
		$propName = PageSchemas::getValueFromObject( $prop_array, 'name' );
		$text .= Html::input( 'smw_connecting_property_num', $propName, array( 'size' => 15 ) ) . "\n";

		return array( $text, $hasExistingValues );
	}

	/**
	 * Returns the HTML necessary for getting information about a regular
	 * semantic property within the Page Schemas 'editschema' page.
	 */
	public static function getFieldEditingHTML( $psTemplateField ) {
		global $smwgContLang;

		$prop_array = array();
		$hasExistingValues = false;
		if ( !is_null( $psTemplateField ) ) {
			$prop_array = $psTemplateField->getObject('semanticmediawiki_Property');
			if ( !is_null( $prop_array ) ) {
				$hasExistingValues = true;
			}
		}
		$html_text = '<p>' . wfMessage( 'ps-optional-name' )->text() . ' ';
		$propName = PageSchemas::getValueFromObject( $prop_array, 'name' );
		$html_text .= Html::input( 'smw_property_name_num', $propName, array( 'size' => 15 ) ) . "\n";
		$propType = PageSchemas::getValueFromObject( $prop_array, 'Type' );
		$select_body = "";
		$datatype_labels = $smwgContLang->getDatatypeLabels();
		foreach ( $datatype_labels as $label ) {
			$optionAttrs = array();
			if ( $label == $propType) {
				$optionAttrs['selected'] = 'selected';
			}
			$select_body .= "\t" . Xml::element( 'option', $optionAttrs, $label ) . "\n";
		}
		$propertyDropdownAttrs = array(
			'id' => 'property_dropdown',
			'name' => 'smw_property_type_num',
			'value' => $propType
		);
		$html_text .= "Type: " . Xml::tags( 'select', $propertyDropdownAttrs, $select_body ) . "</p>\n";

		// This can't be last, because of the hacky way the XML is
		// ocnstructed from this form's output.
		if ( defined( 'SF_VERSION' ) ) {
			$html_text .= '<p>' . wfMessage( 'sf_createproperty_linktoform' )->text() . ' ';
			$linkedForm = PageSchemas::getValueFromObject( $prop_array, 'LinkedForm' );
			$html_text .= Html::input( 'smw_linked_form_num', $linkedForm, array( 'size' => 15 ) ) . "\n";
			$html_text .= "(for Page properties only)</p>\n";
		}

		$html_text .= '<p>If you want this property to only be allowed to have certain values, enter the list of allowed values, separated by commas (if a value contains a comma, replace it with "\,"):</p>';
		$allowedValsInputAttrs = array(
			'size' => 80
		);
		$allowedValues = PageSchemas::getValueFromObject( $prop_array, 'allowed_values' );
		if ( is_null( $allowedValues ) ) {
			$allowed_val_string = '';
		} else {
			$allowed_val_string = implode( ', ', $allowedValues );
		}
		$html_text .= '<p>' . Html::input( 'smw_values_num', $allowed_val_string, 'text', $allowedValsInputAttrs ) . "</p>\n";

		return array( $html_text, $hasExistingValues );
	}

	/**
	 * Creates the property page for each property specified in the
	 * passed-in Page Schemas XML object.
	 */
	public static function generatePages( $pageSchemaObj, $selectedPages ) {
		global $smwgContLang, $wgUser;

		$datatypeLabels = $smwgContLang->getDatatypeLabels();
		$pageTypeLabel = $datatypeLabels['_wpg'];

		$jobs = array();
		$jobParams = array();
		$jobParams['user_id'] = $wgUser->getId();

		// First, create jobs for all "connecting properties".
		$psTemplates = $pageSchemaObj->getTemplates();
		foreach ( $psTemplates as $psTemplate ) {
			$smwConnectingPropertyName = self::getConnectingPropertyName( $psTemplate );
			if ( is_null( $smwConnectingPropertyName ) ) {
				continue;
			}
			$propTitle = Title::makeTitleSafe( SMW_NS_PROPERTY, $smwConnectingPropertyName );
			if ( !in_array( $propTitle, $selectedPages ) ) {
				continue;
			}

			$jobParams['page_text'] = self::createPropertyText( $pageTypeLabel, null, null );
			$jobs[] = new PSCreatePageJob( $propTitle, $jobParams );
		}

		// Second, create jobs for all regular properties.
		$propertyDataArray = self::getAllPropertyData( $pageSchemaObj );
		foreach ( $propertyDataArray as $propertyData ) {
			$propTitle = Title::makeTitleSafe( SMW_NS_PROPERTY, $propertyData['name'] );
			if ( !in_array( $propTitle, $selectedPages ) ) {
				continue;
			}
			$propertyType = array_key_exists( 'Type', $propertyData ) ? $propertyData['Type'] : null;
			$propertyAllowedValues = array_key_exists( 'allowed_values', $propertyData ) ? $propertyData['allowed_values'] : null;
			$propertyLinkedForm = array_key_exists( 'LinkedForm', $propertyData ) ? $propertyData['LinkedForm'] : null;
			$jobParams['page_text'] = self::createPropertyText( $propertyType, $propertyAllowedValues, $propertyLinkedForm );
			$jobs[] = new PSCreatePageJob( $propTitle, $jobParams );
		}
		if ( class_exists( 'JobQueueGroup' ) ) {
			JobQueueGroup::singleton()->push( $jobs );
		} else {
			// MW <= 1.20
			Job::batchInsert( $jobs );
		}
	}

	/**
	 * Creates the text for a property page.
	 */
	static public function createPropertyText( $propertyType, $allowedValues, $linkedForm = null ) {
		/**
		 * @var SMWLanguage $smwgContLang
		 */
		global $smwgContLang, $wgContLang;

		$propLabels = $smwgContLang->getPropertyLabels();
		$hasTypeLabel = $propLabels['_TYPE'];
		$typeTag = "[[$hasTypeLabel::$propertyType]]";
		$text = wfMessage( 'smw-createproperty-isproperty', $typeTag )->inContentLanguage()->text();

		if ( $linkedForm !== '' && defined( 'SF_VERSION' ) ) {
			global $sfgContLang;
			$sfPropLabels = $sfgContLang->getPropertyLabels();
			$defaultFormTag = "[[{$sfPropLabels[SF_SP_HAS_DEFAULT_FORM]}::$linkedForm]]";
			$text .= ' ' . wfMessage( 'sf_property_linkstoform', $defaultFormTag )->inContentLanguage()->text();
		}

		if ( $allowedValues != null) {
			$text .= "\n\n" . wfMessage( 'smw-createproperty-allowedvals', $wgContLang->formatNum( count( $allowedValues ) ) )->inContentLanguage()->text();

			foreach ( $allowedValues as $value ) {
				$prop_labels = $smwgContLang->getPropertyLabels();
				$text .= "\n* [[" . $prop_labels['_PVAL'] . "::$value]]";
			}
		}

		return $text;
	}

	/**
	 * Returns either the "connecting property", or a field property, based
	 * on the XML passed from the Page Schemas extension.
	*/
	public static function createPageSchemasObject( $tagName, $xml ) {
		if ( $tagName == "semanticmediawiki_ConnectingProperty" ) {
			foreach ( $xml->children() as $tag => $child ) {
				if ( $tag == $tagName ) {
					$smw_array = array();
					$propName = $child->attributes()->name;
					$smw_array['name'] = (string)$propName;
					foreach ( $child->children() as $prop => $value ) {
						$smw_array[$prop] = (string)$value;
					}
					return $smw_array;
				}
			}
		} elseif ( $tagName == "semanticmediawiki_Property" ) {
			foreach ( $xml->children() as $tag => $child ) {
				if ( $tag == $tagName ) {
					$smw_array = array();
					$propName = $child->attributes()->name;
					$smw_array['name'] = (string)$propName;
					$allowed_values = array();
					$count = 0;
					foreach ( $child->children() as $prop => $value ) {
						if ( $prop == "AllowedValue" ) {
							$allowed_values[$count++] = $value;
						} else {
							$smw_array[$prop] = (string)$value;
						}
					}
					$smw_array['allowed_values'] = $allowed_values;
					return $smw_array;
				}
			}
		}
		return null;
	}
}
