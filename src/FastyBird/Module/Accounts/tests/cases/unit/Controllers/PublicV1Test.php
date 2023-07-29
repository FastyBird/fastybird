<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Controllers;

use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata;
use FastyBird\Module\Accounts\Exceptions;
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
final class PublicV1Test extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Exceptions\InvalidArgument
	 * @throws InvalidArgumentException
	 * @throws Nette\DI\MissingServiceException
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 *
	 * @dataProvider resetIdentity
	 */
	public function testResetIdentity(string $url, string $body, int $statusCode, string $fixture): void
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
		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
	}

	/**
	 * @return array<string, array<bool|string|int|null>>
	 */
	public static function resetIdentity(): array
	{
		return [
			// Valid responses
			//////////////////
			'request' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.json',
				),
				StatusCodeInterface::STATUS_NO_CONTENT,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.json',
			],

			// Invalid responses
			////////////////////
			'missingRequired' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.missing.required.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.missing.required.json',
			],
			'invalidType' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.invalidType.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/invalid.type.json',
			],
			'unknown' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.invalid.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'deleted' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.deleted.json',
				),
				StatusCodeInterface::STATUS_NOT_FOUND,
				__DIR__ . '/../../../fixtures/Controllers/responses/generic/notFound.json',
			],
			'blocked' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.blocked.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.blocked.json',
			],
			'notActivated' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/reset-identity',
				file_get_contents(
					__DIR__ . '/../../../fixtures/Controllers/requests/public/account.identities.passwordRequest.notActivated.json',
				),
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				__DIR__ . '/../../../fixtures/Controllers/responses/public/account.identities.passwordRequest.notActivated.json',
			],
		];
	}

}
