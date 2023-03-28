<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Devices\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Devices\Tests\Tools;
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

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DevicePropertiesV1Test extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider devicePropertiesRead
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
	public static function devicePropertiesRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.index.json',
			],
			'readAllPaging' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.index.paging.json',
			],
			'readOne' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.read.json',
			],
			'readRelationshipsDevice' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/relationships/device',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.relationships.device.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsDeviceUnknownProperty' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4/relationships/device',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownDevice' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c/relationships/device',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllMissingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider devicePropertiesCreate
	 */
	public function testCreate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
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
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function devicePropertiesCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'create' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.create.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.create.missing.required.json',
			],
			'notUnique' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.notUnique.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.create.notUnique.json',
			],
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'missingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider devicePropertiesUpdate
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
	public static function devicePropertiesUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.update.json',
			],

			// Invalid responses
			////////////////////
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'missingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/device.properties.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider devicePropertiesDelete
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
	public static function devicePropertiesDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.properties.delete.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/9f5e5560-72f2-487a-a382-d73d842ba538',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'missingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/properties/28bc0d38-2f7c-4a71-aa74-27b102f8df4c',
				'Bearer ' . self::VALID_TOKEN_USER,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
