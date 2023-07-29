<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata;
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
final class ConnectorsV1Test extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider connectorsRead
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
	public static function connectorsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/connectors.index.json',
			],
			'readAllPaging' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/connectors.index.paging.json',
			],
			'readOne' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/connectors.read.json',
			],
			'readRelationshipsDevices' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/relationships/devices',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/connectors.relationships.devices.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5ae5',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readAllMissingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
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
	 * @dataProvider connectorsUpdate
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
	public static function connectorsUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/connectors.update.json',
			],

			// Invalid responses
			////////////////////
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::VALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'missingToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'notAllowed' => [
				'/' . Metadata\Constants::MODULE_DEVICES_PREFIX . '/v1/connectors/17c59dfa-2edd-438e-8c49-faa4e38e5a5e',
				'Bearer ' . self::VALID_TOKEN_USER,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/connectors.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
		];
	}

}
