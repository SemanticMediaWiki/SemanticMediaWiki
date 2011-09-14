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

class SMWPageSchemas {

	function parseFieldElements( $field_xml, &$text_object ) {
		foreach ( $field_xml->children() as $tag => $child ) {
			if ( $tag == "semanticmediawiki_Property" ) {
				$text = "";
				$text = PageSchemas::tableMessageRowHTML( "paramAttr", "SemanticMediaWiki", (string)$tag );
				$propName = $child->attributes()->name;			    
				// this means object has already been initialized by some other extension.
				$text .= PageSchemas::tableMessageRowHTML( "paramAttrMsg", "name", (string)$propName );
				foreach ( $child->children() as $prop => $value ) {
					$text .= PageSchemas::tableMessageRowHTML("paramAttrMsg", $prop, (string)$value );
				}
				$text_object['smw'] = $text;
			}
		}
		return true;
	}

	function getPageList( $psSchemaObj , &$genPageList ) {
		$template_all = $psSchemaObj->getTemplates();
		foreach ( $template_all as $template ) {
			$field_all = $template->getFields();
			$field_count = 0; //counts the number of fields
			foreach( $field_all as $field ) { //for each Field, retrieve smw properties and fill $prop_name , $prop_type 
				$field_count++;
				$smw_array = $field->getObject('semanticmediawiki_Property');   //this returns an array with property values filled
				$prop_array = $smw_array['smw'];
				if($prop_array != null){
					$title = Title::makeTitleSafe( SMW_NS_PROPERTY, $prop_array['name'] );
					$genPageList[] = $title;
				}
			}
		}
		return true;
	}

	function getFieldXML( $request, &$xmlArray ) {
		$templateNum = -1;
		$xmlPerField = array();
		foreach ( $request->getValues() as $var => $val ) {
			if ( substr( $var, 0, 18 ) == 'smw_property_name_' ) {
				$templateNum = substr($var,18,1);
				$xml = '<semanticmediawiki_Property name="'.$val.'" >';
			} elseif ( substr( $var, 0, 18 ) == 'smw_property_type_'){
				$xml .= '<Type>'.$val.'</Type>';
			} elseif ( substr( $var, 0, 11 ) == 'smw_values_') {
				if ( $val != '' ) {
					// replace the comma substitution character that has no chance of
					// being included in the values list - namely, the ASCII beep
					$listSeparator = ',';
					$allowed_values_str = str_replace( "\\$listSeparator", "\a", $val );
					$allowed_values_array = explode( $listSeparator, $allowed_values_str );
					foreach ( $allowed_values_array as $i => $value ) {
						// replace beep back with comma, trim
						$value = str_replace( "\a", $listSeparator, trim( $value ) );
						$xml .= '<AllowedValue>'.$value.'</AllowedValue>';
					}
				}
				$xml .= '</semanticmediawiki_Property>';
				$xmlPerField[] = $xml;
			}
		}
		$xmlArray['smw'] = $xmlPerField;
		return true;
	}

	function getFieldHTML( $field, &$text_extensions ) {
		global $smwgContLang;
		$datatype_labels = $smwgContLang->getDatatypeLabels();
		if ( !is_null( $field ) ) {
			$smw_array = $field->getObject('semanticmediawiki_Property'); //this returns an array with property values filled
			$prop_array = $smw_array['smw'];
		} else {
			$prop_array = array();
		}
		$html_text = '<p>Property name: ';
		if ( array_key_exists( 'name', $prop_array ) ) {
			$propName = $prop_array['name'];
		} else {
			$propName = null;
		}
		$html_text .= Html::input( 'smw_property_name_num', $propName, array( 'size' => 15 ) ) . "\n";
		if ( array_key_exists( 'Type', $prop_array ) ) {
			$propType = $prop_array['Type'];
		} else {
			$propType = null;
		}
		$select_body = "";
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
		if ( array_key_exists( 'allowed_value_array', $prop_array ) ) {
			$allowed_val_string = implode( ', ', $prop_array['allowed_value_array'] );
		} else {
			$allowed_val_string = '';
		}
		$html_text .= '<p>' . Html::input( 'smw_values_num', $allowed_val_string, 'text', $allowedValsInputAttrs ) . "</p>\n";

		$text_extensions['smw'] = array( 'Property', '#DEF', $html_text );

		return true;
	}

	function generatePages( $psSchemaObj, $toGenPageList ) {
		$template_all = $psSchemaObj->getTemplates();
		foreach ( $template_all as $template ) {
			$field_all = $template->getFields();
			$field_count = 0; //counts the number of fields
			foreach( $field_all as $field ) { //for each Field, retrieve smw properties and fill $prop_name , $prop_type 
				$field_count++;
				$smw_array = $field->getObject('semanticmediawiki_Property'); //this returns an array with property values filled
				$prop_array = $smw_array['smw'];
				if($prop_array != null){
					$title = Title::makeTitleSafe( SMW_NS_PROPERTY, $prop_array['name'] );
					$key_title = PageSchemas::titleString( $title );
					if(in_array( $key_title, $toGenPageList )){
						self::createProperty( $prop_array['name'], $prop_array['Type'], $prop_array['allowed_value_array'] ) ;
					}
				}
			}
		}
		return true;
	}

	function createPropertyText( $property_type, $allowed_value_array ) {
		global $smwgContLang;
		$prop_labels = $smwgContLang->getPropertyLabels();
		$type_tag = "[[{$prop_labels['_TYPE']}::$property_type]]";
		$text = wfMsgForContent( 'ps-property-isproperty', $type_tag );
		if ( $allowed_value_array != null) {
			// replace the comma substitution character that has no chance of
			// being included in the values list - namely, the ASCII beep
			$text .= "\n\n" . wfMsgExt( 'ps-property-allowedvals', array( 'parsemag', 'content' ), count( $allowed_value_array ) );
			foreach ( $allowed_value_array as $i => $value ) {
				// replace beep back with comma, trim
				$value = str_replace( "\a",',' , trim( $value ) );
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

	function createProperty( $prop_name, $prop_type, $allowed_value_array ) {
		global $wgUser;
		$title = Title::makeTitleSafe( SMW_NS_PROPERTY, $prop_name );
		$text = self::createPropertyText( $prop_type, $allowed_value_array );
		$jobs = array();
		$params = array();
		$params['user_id'] = $wgUser->getId();
		$params['page_text'] = $text;
		$jobs[] = new PSCreatePageJob( $title, $params );
		Job::batchInsert( $jobs );
		return true;
	}

	/**
	* Returns the property based on the XML passed from the Page Schemas extension 
	*/
	function createPageSchemasObject( $objectName, $xmlForField, &$object ) {
		$smw_array = array();
		if ( $objectName == "semanticmediawiki_Property" ) {
			foreach ( $xmlForField->children() as $tag => $child ) {
				if ( $tag == $objectName ) {
					$propName = $child->attributes()->name;    
					//this means object has already been initialized by some other extension.
					$smw_array['name']=(string)$propName;
					$allowed_value_array = array();
					$count = 0;
					foreach ( $child->children() as $prop => $value ) {
						if ( $prop == "AllowedValue" ) {
							$allowed_value_array[$count++] = $value;
						} else {
							$smw_array[$prop] = (string)$value;
						}
					}
					$smw_array['allowed_value_array'] = $allowed_value_array;
				}
			}
			$object['smw'] = $smw_array;
		}
		return true;
	}
}

