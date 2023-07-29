<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

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

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class AccountV1Test extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider accountRead
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
	public static function accountRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'read' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.json',
			],
			'readUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.user.json',
			],
			'readWithIncluded' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me?include=emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.read.included.json',
			],
			'readRelationshipsEmails' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.emails.json',
			],
			'readRelationshipsIdentities' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_IDENTITIES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.identities.json',
			],
			'readRelationshipsRoles' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_ROLES,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.relationship.roles.json',
			],

			// Invalid responses
			////////////////////
			'readRelationshipsUnknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/relationships/' . Schemas\Accounts\Account::RELATIONSHIPS_EMAILS,
				'Bearer ' . self::EXPIRED_TOKEN,
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
	 * @dataProvider accountUpdate
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
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function accountUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.update.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/account.update.missing.required.json',
			],
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::USER_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'noToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				null,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'',
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(__DIR__ . '/../../../fixtures/Controllers/requests/account/account.update.json'),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
