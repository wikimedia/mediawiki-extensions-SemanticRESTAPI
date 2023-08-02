<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Rest\SimpleHandler;
use Wikimedia\ParamValidator\ParamValidator;

/**
 * Class to get the semantic data of a page in the simplest and most useful format
 * GET /semantic/v0/data/{title}
 */
class SemanticRESTAPI extends SimpleHandler {

	/**
	 * @var string User agent for querying the API
	 */
	private static $userAgent =
		'Extension:SemanticRESTAPI/1.0 (https://www.mediawiki.org/wiki/Extension:SemanticRESTAPI)';

	public function run( $title ) {
		// Query the properties
		$query = [
			'action' => 'askargs',
			'format' => 'json',
			'formatversion' => 2,
			'api_version' => 3,
			'conditions' => 'Property:+'
		];
		$data = self::queryAPI( $query );

		$properties = [];
		foreach ( $data as $property ) {
			$property = array_key_first( $property );
			$property = substr( $property, strpos( $property, ':' ) + 1 );
			$properties[] = $property;
		}
		// echo '<pre>'; var_dump( $properties ); exit; // Uncomment to debug

		// We use 'ask' instead of 'askargs' to bypass a harcoded limit in the number of printouts
		$query = [
			'action' => 'ask',
			'format' => 'json',
			'formatversion' => 2,
			'api_version' => 3,
			'query' => "[[$title]]|?" . implode( '|?', $properties ),
		];
		$data = self::queryAPI( $query );
		if ( !$data ) {
			return $data;
		}
		$data = array_shift( $data );
		$data = array_shift( $data );
		$data = $data['printouts'];
		// echo '<pre>'; var_dump( $data ); exit; // Uncomment to debug

		// Clean the data
		foreach ( $data as $property => $values ) {
			$value = [];
			foreach ( $values as $v ) {
				if ( $v && is_array( $v ) ) {
					$value[] = $v['fulltext'] ?? $v['raw'];
				} else {
					$value[] = $v;
				}
			}
			$value = implode( ', ', $value );
			$data[ $property ] = $value;
		}
		$data = array_filter( $data );
		return $data;
	}

	/**
	 * Query the API and unwrap the data or handle errors
	 */
	public function queryAPI( $query ) {
		global $wgServer, $wgScriptPath;
		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()
			->create( $wgServer . $wgScriptPath . '/api.php?' . http_build_query( $query ) );
		$request->setUserAgent( self::$userAgent );
		$status = $request->execute();
		if ( !$status->isOK() ) {
			return $status;
		}
		$data = $request->getContent();
		$data = FormatJson::decode( $data, true );
		$data = $data['query'];
		$data = $data['results'];
		return $data;
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
