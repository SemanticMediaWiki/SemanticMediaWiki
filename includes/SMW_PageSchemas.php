<?php

/**
 * Functions for handling Semantic MediaWiki data within the Page Schemas
 * extension.
 * 
 * @author Ankit Garg
 * @author Yaron Koren
 * @file SMW_PageSchemas.php
 * @ingroup SMW
 */

class SMWPageSchemas extends PSExtensionHandler {

	public static function getDisplayColor() {
		return '#DEF';
	}

	public static function getFieldDisplayString() {
		return 'Semantic property';
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
				if ( !array_key_exists( 'name', $prop_array ) ) {
					continue;
				}
				if ( empty( $prop_array['name'] ) ) {
					continue;
				}
				$propertyDataArray[] = $prop_array;
			}
		}
		return $propertyDataArray;
	}

	/**
	 * Sets the list of property pages defined by the passed-in
	 * Page Schemas object.
	 */
	public static function getPagesToGenerate( $pageSchemaObj ) {
		$pagesToGenerate = array();
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
		$html_text = '<p>' . wfMsg( 'ps-optional-name' ) . ' ';
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
		global $wgUser;

		$jobs = array();
		$jobParams = array();
		$jobParams['user_id'] = $wgUser->getId();
		$propertyDataArray = self::getAllPropertyData( $pageSchemaObj );
		foreach ( $propertyDataArray as $propertyData ) {
			$propTitle = Title::makeTitleSafe( SMW_NS_PROPERTY, $propertyData['name'] );
			if ( !in_array( $propTitle, $selectedPages ) ) {
				continue;
			}
			$jobParams['page_text'] = self::createPropertyText( $propertyData['Type'], $propertyData['allowed_values'] );
			$jobs[] = new PSCreatePageJob( $propTitle, $jobParams );
		}
		Job::batchInsert( $jobs );
	}

	/**
	 * Creates the text for a property page.
	 */
	function createPropertyText( $propertyType, $allowedValues ) {
		global $smwgContLang;
		$propLabels = $smwgContLang->getPropertyLabels();
		$hasTypeLabel = $propLabels['_TYPE'];
		$typeTag = "[[$hasTypeLabel::$propertyType]]";
		$text = wfMsgForContent( 'smw-createproperty-isproperty', $typeTag );
		if ( $allowedValues != null) {
			$text .= "\n\n" . wfMsgExt( 'smw-createproperty-allowedvals', array( 'parsemag', 'content' ), count( $allowedValues ) );
			foreach ( $allowedValues as $value ) {
				if ( method_exists( $smwgContLang, 'getPropertyLabels' ) ) {
					$prop_labels = $smwgContLang->getPropertyLabels();
					$text .= "\n* [[" . $prop_labels['_PVAL'] . "::$value]]";
				} else {
					$spec_props = $smwgContLang->getSpecialPropertiesArray();
					$text .= "\n* [[" . $spec_props[SMW_SP_POSSIBLE_VALUE] . "::$value]]";
				}
			}
		}
		return $text;
	}

	/**
	 * Returns the property based on the XML passed from the Page Schemas
	 * extension.
	*/
	public static function createPageSchemasObject( $tagName, $xml ) {
		if ( $tagName == "semanticmediawiki_Property" ) {
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
