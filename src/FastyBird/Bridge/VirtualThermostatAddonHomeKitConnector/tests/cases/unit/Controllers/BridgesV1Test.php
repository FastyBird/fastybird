<?php declare(strict_types = 1);

namespace FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Cases\Unit\DbTestCase;
use FastyBird\Bridge\VirtualThermostatAddonHomeKitConnector\Tests\Tools;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata;
use Fig\Http\Message\RequestMethodInterface;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use IPub\SlimRouter;
use IPub\SlimRouter\Http as SlimRouterHttp;
use Nette;
use Nette\Utils;
use React\Http\Message\ServerRequest;
use RuntimeException;
use function file_get_contents;
use function is_array;
use function str_replace;
use function strval;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class BridgesV1Test extends DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider bridgesRead
	 */
	public function testRead(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function bridgesRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.index.json',
			],
			'readAllPaging' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.index.paging.json',
			],
			'readOne' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.read.json',
			],
			'readRelationshipsProperties' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2/relationships/properties',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.relationships.properties.json',
			],
			'readRelationshipsChannels' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2/relationships/channels',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.relationships.channels.json',
			],
			'readRelationshipsChildren' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2/relationships/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.relationships.children.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/69786d15-fd0c-4d9f-9378-33287c2009af',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownEntity' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009af/relationships/children',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllMissingToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider bridgesCreate
	 */
	public function testCreate(
		string $url,
		string|null $token,
		string $body,
		int $statusCode,
		string $fixture,
	): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			$headers,
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());

		$responseBody = (string) $response->getBody();

		$actual = Utils\Json::decode($responseBody, Utils\Json::FORCE_ARRAY);
		self::assertTrue(is_array($actual));

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
			static function (string $expectation) use ($actual): string {
				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['relationships'])
					&& is_array($actual['data']['relationships'])
					&& isset($actual['data']['relationships']['properties'])
					&& is_array($actual['data']['relationships']['properties'])
					&& isset($actual['data']['relationships']['properties']['data'])
					&& is_array($actual['data']['relationships']['properties']['data'])
					&& isset($actual['data']['relationships']['properties']['data'][0])
					&& is_array($actual['data']['relationships']['properties']['data'][0])
					&& isset($actual['data']['relationships']['properties']['data'][0]['id'])
				) {
					$expectation = str_replace(
						'__PROPERTY_1_PLACEHOLDER__',
						strval($actual['data']['relationships']['properties']['data'][0]['id']),
						$expectation,
					);
				}

				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['relationships'])
					&& is_array($actual['data']['relationships'])
					&& isset($actual['data']['relationships']['properties'])
					&& is_array($actual['data']['relationships']['properties'])
					&& isset($actual['data']['relationships']['properties']['data'])
					&& is_array($actual['data']['relationships']['properties']['data'])
					&& isset($actual['data']['relationships']['properties']['data'][1])
					&& is_array($actual['data']['relationships']['properties']['data'][1])
					&& isset($actual['data']['relationships']['properties']['data'][1]['id'])
				) {
					$expectation = str_replace(
						'__PROPERTY_2_PLACEHOLDER__',
						strval($actual['data']['relationships']['properties']['data'][1]['id']),
						$expectation,
					);
				}

				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['relationships'])
					&& is_array($actual['data']['relationships'])
					&& isset($actual['data']['relationships']['properties'])
					&& is_array($actual['data']['relationships']['properties'])
					&& isset($actual['data']['relationships']['properties']['data'])
					&& is_array($actual['data']['relationships']['properties']['data'])
					&& isset($actual['data']['relationships']['properties']['data'][2])
					&& is_array($actual['data']['relationships']['properties']['data'][2])
					&& isset($actual['data']['relationships']['properties']['data'][2]['id'])
				) {
					$expectation = str_replace(
						'__PROPERTY_3_PLACEHOLDER__',
						strval($actual['data']['relationships']['properties']['data'][2]['id']),
						$expectation,
					);
				}

				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['relationships'])
					&& is_array($actual['data']['relationships'])
					&& isset($actual['data']['relationships']['channels'])
					&& is_array($actual['data']['relationships']['channels'])
					&& isset($actual['data']['relationships']['channels']['data'])
					&& is_array($actual['data']['relationships']['channels']['data'])
					&& isset($actual['data']['relationships']['channels']['data'][0])
					&& is_array($actual['data']['relationships']['channels']['data'][0])
					&& isset($actual['data']['relationships']['channels']['data'][0]['id'])
				) {
					$expectation = str_replace(
						'__CHANNEL_1_PLACEHOLDER__',
						strval($actual['data']['relationships']['channels']['data'][0]['id']),
						$expectation,
					);
				}

				return $expectation;
			},
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function bridgesCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'create' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.create.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.create.missing.required.json',
			],
			'notUnique' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.notUnique.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.create.notUnique.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.invalid.type.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'missingToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider bridgesUpdate
	 */
	public function testUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			$url,
			$headers,
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function bridgesUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.update.json',
			],

			// Invalid responses
			////////////////////
			'invalidType' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.invalid.type.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.invalid.id.json'),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'missingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/bridges.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider bridgesDelete
	 */
	public function testDelete(string $url, string|null $token, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			$url,
			$headers,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function bridgesDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/bridges.delete.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/69786d15-fd0c-4d9f-9378-33287c2009af',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'missingToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/api/' . Metadata\Constants::BRIDGE_VIRTUAL_THERMOSTAT_ADDON_HOMEKIT_CONNECTOR_PREFIX . '/v1/bridges/cfb6e9cc-29a7-48d6-aecb-5f901d79eba2',
				'Bearer ' . self::VALID_TOKEN_USER,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
