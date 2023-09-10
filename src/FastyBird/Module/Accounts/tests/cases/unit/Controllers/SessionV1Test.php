<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use Error;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata;
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
use function is_array;
use function str_replace;
use function strval;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class SessionV1Test extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider sessionRead
	 */
	public function XtestRead(string $url, string|null $token, int $statusCode, string $fixture): void
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
	public static function sessionRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'read' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.read.json',
			],
			'readUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.read.user.json',
			],
			'readRelationshipsAccount' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session/relationships/' . Schemas\Sessions\Session::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.relationship.account.json',
			],

			// Invalid responses
			////////////////////
			'readRelationshipsUnknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session/relationships/' . Schemas\Sessions\Session::RELATIONSHIPS_ACCOUNT,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session/relationships/' . Schemas\Sessions\Session::RELATIONSHIPS_ACCOUNT,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session/relationships/' . Schemas\Sessions\Session::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session/relationships/' . Schemas\Sessions\Session::RELATIONSHIPS_ACCOUNT,
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
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider sessionCreate
	 */
	public function XtestCreate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
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
			$responseBody,
			static function (string $expectation) use ($actual): string {
				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['attributes'])
					&& is_array($actual['data']['attributes'])
					&& isset($actual['data']['attributes']['token'])
					&& isset($actual['data']['attributes']['refresh'])
					&& isset($actual['data']['id'])
				) {
					$expectation = str_replace(
						'__ACCESS_TOKEN__',
						strval($actual['data']['attributes']['token']),
						$expectation,
					);
					$expectation = str_replace(
						'__REFRESH_TOKEN__',
						strval($actual['data']['attributes']['refresh']),
						$expectation,
					);
					$expectation = str_replace('__ENTITY_ID__', strval($actual['data']['id']), $expectation);
				}

				return $expectation;
			},
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function sessionCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'create' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.json',
			],
			'createWithEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.json',
			],

			// Invalid responses
			////////////////////
			'createWithToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'createWithExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'createWithInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.missing.required.json',
			],
			'unknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.unknown.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.unknown.json',
			],
			'invalid' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.invalid.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.invalid.json',
			],
			'deleted' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.deleted.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.deleted.json',
			],
			'blocked' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.blocked.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.blocked.json',
			],
			'notActivated' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.notActivated.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.notActivated.json',
			],
			'approval_waiting' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.create.approvalWaiting.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.create.approvalWaiting.json',
			],
		];
	}

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider sessionUpdate
	 */
	public function XtestUpdate(string $url, string|null $token, string $body, int $statusCode, string $fixture): void
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

		$responseBody = (string) $response->getBody();

		$actual = Utils\Json::decode($responseBody, Utils\Json::FORCE_ARRAY);
		self::assertTrue(is_array($actual));

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			$responseBody,
			static function (string $expectation) use ($actual): string {
				if (
					isset($actual['data'])
					&& is_array($actual['data'])
					&& isset($actual['data']['attributes'])
					&& is_array($actual['data']['attributes'])
					&& isset($actual['data']['attributes']['token'])
					&& isset($actual['data']['attributes']['refresh'])
					&& isset($actual['data']['id'])
				) {
					$expectation = str_replace(
						'__ACCESS_TOKEN__',
						strval($actual['data']['attributes']['token']),
						$expectation,
					);
					$expectation = str_replace(
						'__REFRESH_TOKEN__',
						strval($actual['data']['attributes']['refresh']),
						$expectation,
					);
					$expectation = str_replace('__ENTITY_ID__', strval($actual['data']['id']), $expectation);
				}

				return $expectation;
			},
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function sessionUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.update.json',
			],
			'updateWithEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.json'),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.update.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.update.missing.required.json',
			],
			'unknownRefreshToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.unknown.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.update.unknown.json',
			],
			'updateWithToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'updateWithExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'updateWithInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/session/session.update.json'),
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
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider sessionDelete
	 */
	public function XtestDelete(string $url, string|null $token, int $statusCode, string $fixture): void
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
	public static function sessionDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/session/session.delete.json',
			],

			// Invalid responses
			////////////////////
			'missingToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/session',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
