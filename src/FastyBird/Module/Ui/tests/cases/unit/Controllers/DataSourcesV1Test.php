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
final class DataSourcesV1Test extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider dataSourcesRead
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
	public static function dataSourcesRead(): array
	{
		return [
			'readAll' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.index.json',
			],
			'readAllPaging' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources?page[offset]=1&page[limit]=1',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.index.paging.json',
			],
			'readOne' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.read.json',
			],
			'readOneUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/69786d15-fd0c-4d9f-9378-33287c2009af',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readOneUnknownWidget' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/bb369e71-ada6-4d1a-a5a8-b6ee5cd58296/data-sources/69786d15-fd0c-4d9f-9378-33287c2009af',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsWidget' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377/relationships/widget',
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.readRelationships.widget.json',
			],
			'readRelationshipsUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377/relationships/unknown',
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
	 * @dataProvider dataSourcesCreate
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
	public static function dataSourcesCreate(): array
	{
		return [
			'create' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.create.json',
			],
			'missingRequired' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.create.missing.required.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.missing.required.json',
			],
			'widgetNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/11553443-4564-454d-af04-0dfeef08aa96/data-sources',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.create.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.create.invalidType.json'),
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
	 * @dataProvider dataSourcesUpdate
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
	public static function dataSourcesUpdate(): array
	{
		return [
			'update' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.update.json',
			],
			'invalidType' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.update.invalidType.json'),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.update.idMismatch.json'),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'notFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/774937a7-8565-472e-8e12-fe97cd55a377',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.update.notFound.json'),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'widgetNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/11553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/dataSources.update.json'),
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
	 * @dataProvider dataSourcesDelete
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
	public static function dataSourcesDelete(): array
	{
		return [
			'delete' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/dataSources.delete.json',
			],
			'deleteUnknown' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/15553443-4564-454d-af04-0dfeef08aa96/data-sources/774937a7-8565-472e-8e12-fe97cd55a377',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'dashboardNotFound' => [
				'/api/' . Metadata\Constants::MODULE_UI_PREFIX . '/v1/widgets/11553443-4564-454d-af04-0dfeef08aa96/data-sources/764937a7-8565-472e-8e12-fe97cd55a377',
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
		];
	}

}
