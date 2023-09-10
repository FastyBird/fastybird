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

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class AccountEmailsV1Test extends DbTestCase
{

	private const ADMINISTRATOR_EMAIL_ID = '32ebe3c3-0238-482e-ab79-6b1d9ee2147c';

	private const ADMINISTRATOR_PRIMARY_EMAIL_ID = '0b46d3d6-c980-494a-8b40-f19e6095e610';

	private const USER_EMAIL_ID = '73efbfbd-efbf-bd36-44ef-bfbdefbfbd7a';

	private const UNKNOWN_ID = '83985c13-238c-46bd-aacb-2359d5c921a7';

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider emailsRead
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
	public static function emailsRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.index.json',
			],
			'readAllPaging' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails?page[offset]=1&page[limit]=1',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.index.paging.json',
			],
			'readOne' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.read.json',
			],
			'readRelationshipsAccount' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.relationships.account.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readOneFromOtherUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::USER_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownEntity' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::UNKNOWN_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsFromOtherUserEntity' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::USER_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsNoToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsEmptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsInvalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsExpiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID . '/relationships/' . Schemas\Emails\Email::RELATIONSHIPS_ACCOUNT,
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
	 * @throws Error
	 * @throws Utils\JsonException
	 *
	 * @dataProvider emailsCreate
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
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function emailsCreate(): array
	{
		return [
			// Valid responses
			//////////////////
			'create' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.json',
				),
				StatusCodeInterface::STATUS_CREATED,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.create.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.create.missing.required.json',
			],
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'identifierNotUnique' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/emails.create.identifier.notUnique.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/identifier.notUnique.json',
			],
			'invalidEmail' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.invalid.email.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.invalidEmail.json',
			],
			'usedEmail' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.usedEmail.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.create.usedEmail.json',
			],
			'noToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails',
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.create.json',
				),
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
	 * @dataProvider emailsUpdate
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
	public static function emailsUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.json',
				),
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.update.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.unknown.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'fromOtherUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::USER_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.otherUser.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'noToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/emails/account.emails.update.json',
				),
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
	 * @dataProvider emailsDelete
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
	public static function emailsDelete(): array
	{
		return [
			// Valid responses
			//////////////////
			'delete' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.delete.json',
			],

			// Invalid responses
			////////////////////
			'default' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_PRIMARY_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/emails/account.emails.delete.default.json',
			],
			'unknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'fromOtherUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::USER_EMAIL_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'noToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/emails/' . self::ADMINISTRATOR_EMAIL_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
