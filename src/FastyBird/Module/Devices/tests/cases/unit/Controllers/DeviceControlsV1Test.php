<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Tests\Cases\Unit\Controllers;

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

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class DeviceControlsV1Test extends DbTestCase
{

	/**
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider deviceControlsRead
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
	 * @return Array<string, Array<string|int|null>>
	 */
	public function deviceControlsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.controls.index.json',
			],
			'readAllPaging' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.controls.index.paging.json',
			],
			'readOne' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.controls.read.json',
			],
			'readRelationshipsDevice' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662/relationships/device',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/device.controls.relationships.device.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsDeviceUnknownControl' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4/relationships/device',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknownDevice' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009af/controls/7c055b2b-60c3-4017-93db-e9478d8aa662/relationships/device',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readAllMissingToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				'/v1/devices/69786d15-fd0c-4d9f-9378-33287c2009fa/controls/7c055b2b-60c3-4017-93db-e9478d8aa662',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
