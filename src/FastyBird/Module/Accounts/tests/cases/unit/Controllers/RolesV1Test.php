<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Module\Accounts\Exceptions;
use FastyBird\Module\Accounts\Schemas;
use FastyBird\Module\Accounts\Tests\Cases\Unit\DbTestCase;
use FastyBird\Module\Accounts\Tests\Tools;
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
final class RolesV1Test extends DbTestCase
{

	private const ROLE_ID = 'efbfbdef-bfbd-efbf-bd0f-efbfbd5c4f61';

	private const UNKNOWN_ID = '83985c13-238c-46bd-aacb-2359d5c921a7';

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider rolesRead
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
	public function rolesRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/v1/roles',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.index.json',
			],
			'readAllPaging' => [
				'/v1/roles?page[offset]=1&page[limit]=1',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.index.paging.json',
			],
			'readAllUser' => [
				'/v1/roles',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.index.json',
			],
			'readOne' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.read.json',
			],
			'readOneUser' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.read.json',
			],
			'readChildren' => [
				'/v1/roles/efbfbd04-0158-efbf-bdef-bfbd4defbfbd/children',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.read.children.json',
			],
			'readChildrenUser' => [
				'/v1/roles/efbfbd04-0158-efbf-bdef-bfbd4defbfbd/children',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.read.children.json',
			],
			'readRelationshipsChildren' => [
				'/v1/roles/efbfbd04-0158-efbf-bdef-bfbd4defbfbd/relationships/' . Schemas\Roles\Role::RELATIONSHIPS_CHILDREN,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.relationships.children.json',
			],
			'readRelationshipsParent' => [
				'/v1/roles/' . self::ROLE_ID . '/relationships/' . Schemas\Roles\Role::RELATIONSHIPS_PARENT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.relationships.parent.json',
			],
			'readRelationshipsParentUser' => [
				'/v1/roles/' . self::ROLE_ID . '/relationships/' . Schemas\Roles\Role::RELATIONSHIPS_PARENT,
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.relationships.parent.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/v1/roles/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/v1/roles/' . self::ROLE_ID . '/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownEntity' => [
				'/v1/roles/' . self::UNKNOWN_ID . '/relationships/' . Schemas\Roles\Role::RELATIONSHIPS_CHILDREN,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllNoToken' => [
				'/v1/roles',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneNoToken' => [
				'/v1/roles/' . self::ROLE_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/v1/roles',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/v1/roles/' . self::ROLE_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllExpiredToken' => [
				'/v1/roles',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneExpiredToken' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllInvalidToken' => [
				'/v1/roles',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider rolesUpdate
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
	public function rolesUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/roles/roles.update.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				'/v1/roles/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'noToken' => [
				'/v1/roles/' . self::ROLE_ID,
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/v1/roles/' . self::ROLE_ID,
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'userToken' => [
				'/v1/roles/' . self::USER_TOKEN,
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/v1/roles/' . self::ROLE_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/roles/roles.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
