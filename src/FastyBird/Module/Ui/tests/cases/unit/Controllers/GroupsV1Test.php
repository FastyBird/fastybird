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

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class GroupsV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider groupsRead
	 */
	public function testRead(string $url, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url
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
	public static function groupsRead(): array
	{
		return [
			'readAll' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/groups',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.index.json',
			],
			'readAllPaging' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/groups?page[offset]=1&page[limit]=1',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.index.paging.json',
			],
			'readOne' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.read.json',
			],
			'readOneUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/69786d15-fd0c-4d9f-9378-33287c2009af',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readOneUnknownDashboard' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/bb369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/69786d15-fd0c-4d9f-9378-33287c2009af',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsWidgets' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c/relationships/widgets',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.readRelationships.widgets.json',
			],
			'readRelationshipsDashboard' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c/relationships/dashboard',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.readRelationships.dashboard.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c/relationships/unknown',
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
	 * @dataProvider groupsCreate
	 */
	public function testCreate(string $url, string $body, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			[],
			$body
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
	public static function groupsCreate(): array
	{
		return [
			'create' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/groups',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.create.json',
			],
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/groups',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.create.missing.required.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.missing.required.json',
			],
			'dashboardNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/aa2379d8-8351-44b6-ad8d-73a0abcb7f9c/groups',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.create.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/272379d8-8351-44b6-ad8d-73a0abcb7f9c/groups',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.create.invalidType.json'),
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
	 * @dataProvider groupsUpdate
	 */
	public function testUpdate(string $url, string $body, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			$url,
			[],
			$body
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
	public static function groupsUpdate(): array
	{
		return [
			'update' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.update.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.update.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.update.idMismatch.json'),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'notFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/88f4a14f-7f78-4216-99b8-584ab9229f1c',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.update.notFound.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'dashboardNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/bb369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/groups.update.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
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
	 * @dataProvider groupsDelete
	 */
	public function testDelete(string $url, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			$url
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
	public static function groupsDelete(): array
	{
		return [
			'delete' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/groups.delete.json',
			],
			'deleteUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/ab369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/88f4a14f-7f78-4216-99b8-584ab9229f1c',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'dashboardNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/dashboards/aa369e71-ada6-4d1a-a5a8-b6ee5cd58296/groups/89f4a14f-7f78-4216-99b8-584ab9229f1c',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

}
