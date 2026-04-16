<?php

namespace SMW\Property;

use SMW\DataModel\SemanticData;
use SMW\Property\DeclarationExaminer\ChangePropagationExaminer;
use SMW\Property\DeclarationExaminer\CommonExaminer;
use SMW\Property\DeclarationExaminer\PredefinedPropertyExaminer;
use SMW\Property\DeclarationExaminer\ProtectionExaminer;
use SMW\Property\DeclarationExaminer\UserdefinedPropertyExaminer;
use SMW\Services\ServicesFactory as ApplicationFactory;
use SMW\Store;

/**
 * @license GPL-2.0-or-later
 * @since 3.1
 *
 * @author mwjames
 */
class DeclarationExaminerFactory {

	/**
	 * @since 3.1
	 */
	public function newDeclarationExaminerMsgBuilder(): DeclarationExaminerMsgBuilder {
		return new DeclarationExaminerMsgBuilder();
	}

	/**
	 * @since 3.1
	 */
	public function newDeclarationExaminer( Store $store, ?SemanticData $semanticData = null ): UserdefinedPropertyExaminer {
		$applicationFactory = ApplicationFactory::getInstance();
		$settings = $applicationFactory->getSettings();

		$commonExaminer = new CommonExaminer(
			$store,
			$semanticData
		);

		$commonExaminer->setNamespacesWithSemanticLinks(
			$settings->get( 'smwgNamespacesWithSemanticLinks' )
		);

		$commonExaminer->setPropertyReservedNameList(
			$settings->get( 'smwgPropertyReservedNameList' )
		);

		$changePropagationExaminer = new ChangePropagationExaminer(
			$commonExaminer,
			$store,
			$semanticData
		);

		$changePropagationExaminer->setChangePropagationProtection(
			$settings->get( 'smwgChangePropagationProtection' )
		);

		$protectionExaminer = new ProtectionExaminer(
			$changePropagationExaminer,
			$applicationFactory->singleton( 'ProtectionValidator' )
		);

		$predefinedPropertyExaminer = new PredefinedPropertyExaminer(
			$protectionExaminer
		);

		$userdefinedPropertyExaminer = new UserdefinedPropertyExaminer(
			$predefinedPropertyExaminer,
			$store
		);

		return $userdefinedPropertyExaminer;
	}

}
