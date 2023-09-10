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
final class AccountIdentitiesV1Test extends DbTestCase
{

	private const ADMINISTRATOR_IDENTITY_ID = '77331268-efbf-bd34-49ef-bfbdefbfbd04';

	private const USER_IDENTITY_ID = 'faf7a863-a49c-4428-a757-1de537773355';

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
	 * @dataProvider identitiesRead
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
	public static function identitiesRead(): array
	{
		return [
			// Valid responses
			//////////////////
			'readAll' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.index.json',
			],
			'readAllPaging' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities?page[offset]=1&page[limit]=1',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.index.paging.json',
			],
			'readOne' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.read.json',
			],
			'readRelationshipsAccount' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.relationships.account.json',
			],

			// Invalid responses
			////////////////////
			'readOneUnknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readOneFromOtherUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::USER_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsUnknown' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID . '/relationships/unknown',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/relation.unknown.json',
			],
			'readRelationshipsUnknownEntity' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::UNKNOWN_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readRelationshipsFromOtherUserEntity' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::USER_IDENTITY_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'readAllNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities',
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneNoToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readAllEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities',
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneEmptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readOneExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities',
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readAllExpiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities',
				'Bearer ' . self::EXPIRED_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readOneInvalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsNoToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
				null,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsEmptyToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
				'',
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'readRelationshipsInvalidToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
				'Bearer ' . self::INVALID_TOKEN,
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'readRelationshipsExpiredToken' => [
				// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID . '/relationships/' . Schemas\Identities\Identity::RELATIONSHIPS_ACCOUNT,
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
	 * @dataProvider identitiesUpdate
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
	 * @return array<string, array<(bool|string|int|null)>>
	 */
	public static function identitiesUpdate(): array
	{
		return [
			// Valid responses
			//////////////////
			'update' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.json',
				),
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.update.json',
			],

			// Invalid responses
			////////////////////
			'unknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::UNKNOWN_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidPassword' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.invalid.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.update.invalid.json',
			],
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/account/identities/account.identities.update.missing.required.json',
			],
			'fromOtherUser' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::USER_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.otherUser.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.invalid.type.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'idMismatch' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.invalid.id.json',
				),
				StatusCodeInterface::STATUS_BAD_REQUEST,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.identifier.json',
			],
			'noToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				null,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'emptyToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.json',
				),
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/forbidden.json',
			],
			'invalidToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::INVALID_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
			'expiredToken' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/me/identities/' . self::ADMINISTRATOR_IDENTITY_ID,
				'Bearer ' . self::EXPIRED_TOKEN,
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/account/identities/account.identities.update.json',
				),
				StatusCodeInterface::STATUS_UNAUTHORIZED,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/unauthorized.json',
			],
		];
	}

}
