<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Library\Metadata;
use FastyBird\Module\Ui\Tests;
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
final class DashboardsV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider dashboardsRead
	 */
	public function testRead(string $url, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(RequestMethodInterface::METHOD_GET, $url);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function dashboardsRead(): array
	{
		return [
			'readAll' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.index.json',
			],
			'readAllPaging' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards?page[offset]=1&page[limit]=1',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.index.paging.json',
			],
			'readOne' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.read.json',
			],
			'readOneUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/69786d15-fd0c-4d9f-9378-33287c2009af',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsGroups' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/relationships/widgets',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.readRelationships.widgets.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/relationships/unknown',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
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
	 * @dataProvider dashboardsCreate
	 */
	public function testCreate(string $url, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			[],
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function dashboardsCreate(): array
	{
		return [
			'create' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dashboards.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.create.json',
			],
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/dashboards.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.missing.required.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/dashboards.create.invalidType.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
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
	 * @dataProvider dashboardsUpdate
	 */
	public function testUpdate(string $url, string $body, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			$url,
			[],
			$body,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function dashboardsUpdate(): array
	{
		return [
			'update' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dashboards.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.update.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/dashboards.update.invalidType.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/dashboards.update.idMismatch.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
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
	 * @dataProvider dashboardsDelete
	 */
	public function testDelete(string $url, int $statusCode, string $fixture): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			$url,
		);

		$response = $router->handle($request);

		self::assertTrue($response instanceof SlimRouterHttp\Response);
		self::assertSame($statusCode, $response->getStatusCode());
		Tests\Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<string|int|null>>
	 */
	public static function dashboardsDelete(): array
	{
		return [
			'delete' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c',
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/dashboards.delete.json',
			],
			'deleteUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/69786d15-fd0c-4d9f-9378-33287c2009af',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

}
