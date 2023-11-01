<?php

use MediaWiki\Rest\SimpleHandler;
use SMW\DIWikiPage;
use SMW\StoreFactory;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class to get the semantic data of a page in the simplest and most useful format
 * GET /v1/page/{title}/semantic
 */
class SemanticRESTAPI extends SimpleHandler {

	public function run( $title ) {
		$subject = DIWikiPage::newFromText( $title );
		$store = StoreFactory::getStore();
		$data = $store->getSemanticData( $subject );
		$properties = $data->getProperties();
		$output = [];
		foreach ( $properties as $property ) {
			$values = $data->getPropertyValues( $property );
			$strings = [];
			foreach ( $values as $value ) {
				switch ( $value->getDIType() ) {
					case SMWDataItem::TYPE_NUMBER:
						$strings[] = $value->getNumber();
						break;
					case SMWDataItem::TYPE_BLOB:
						$strings[] = $value->getString();
						break;
					case SMWDataItem::TYPE_BOOLEAN:
						$strings[] = $value->getBoolean();
						break;
					case SMWDataItem::TYPE_URI:
						$strings[] = $value->getURI();
						break;
					case SMWDataItem::TYPE_TIME:
						$strings[] = $value->asDateTime()->format( DATE_RFC850 );
						break;
					case SMWDataItem::TYPE_GEO:
						$strings[] = implode( ', ', array_values( $value->getCoordinateSet() ) );
						break;
					case SMWDataItem::TYPE_WIKIPAGE:
						$strings[] = $value->getTitle()->getFullText();
						break;
				}
			}
			if ( $strings ) {
				$label = $property->getCanonicalLabel();
				if ( $label ) {
					$output[ $label ] = implode( ', ', $strings );
				}
			}
		}
		return $output;
	}

	public function needsWriteAccess() {
		return false;
	}

	public function getParamSettings() {
		return [
			'title' => [
				self::PARAM_SOURCE => 'path',
				ParamValidator::PARAM_TYPE => 'string',
				ParamValidator::PARAM_REQUIRED => true,
			]
		];
	}
}
