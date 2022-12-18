<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Tests\Cases\Unit\Controllers;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Triggers\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Triggers\Tests\Tools;
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
final class TriggerControlsV1Test extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
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
	 * @return array<string, array<string|int|null>>
	 */
	public function deviceControlsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/trigger.controls.index.json',
			],
			'readAllPaging' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls?page[offset]=1&page[limit]=1',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/trigger.controls.index.paging.json',
			],
			'readOne' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/trigger.controls.read.json',
			],
			'readRelationshipsTrigger' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a/relationships/trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/trigger.controls.relationships.trigger.json',
			],

			// Invalid responses
			////////////////////
			'readForInvalidTriggerType' => [
				'/v1/triggers/2cea2c1b-4790-4d82-8a9f-902c7155ab36/controls',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readOneUnknown' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsTriggerUnknownControl' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/28bc0d38-2f7c-4a71-aa74-27b102f8dfc4/relationships/trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknownTrigger' => [
				'/v1/triggers/69786d15-fd0c-4d9f-9378-33287c2009af/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a/relationships/trigger',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a/relationships/unknown',
				'Bearer ' . self::VALID_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readAllMissingToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneMissingToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllInvalidToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				'/v1/triggers/c64ba1c4-0eda-4cab-87a0-4d634f7b67f4/controls/177d6fc7-1905-4fd9-b847-e2da8189dd6a',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
