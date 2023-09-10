<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Tests\Cases\Unit\Middleware;

use Error;
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

final class AccessTest extends DbTestCase
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
	 * @dataProvider permissionAnnotation
	 */
	public function XtestPermissionAnnotation(
		string $url,
		string $method,
		string $body,
		string $token,
		int $statusCode,
		string $fixture,
	): void
	{
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$request = new ServerRequest(
			$method,
			$url,
			[
				'authorization' => $token,
			],
			$body,
		);

		$response = $router->handle($request);

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody(),
		);
		self::assertSame($statusCode, $response->getStatusCode());
		self::assertTrue($response instanceof SlimRouterHttp\Response);
	}

	/**
	 * @return array<string, array<int|string>>
	 */
	public static function permissionAnnotation(): array
	{
		return [
			'readAllowed' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/roles',
				RequestMethodInterface::METHOD_GET,
				'',
				'Bearer ' . self::ADMINISTRATOR_TOKEN,
				StatusCodeInterface::STATUS_OK,
				__DIR__ . '/../../../fixtures/Middleware/responses/roles.index.json',
			],
			'updateForbidden' => [
				'/' . Metadata\Constants::MODULE_ACCOUNTS_PREFIX . '/v1/roles/efbfbdef-bfbd-efbf-bd0f-efbfbd5c4f61',
				RequestMethodInterface::METHOD_PATCH,
				__DIR__ . '/../../../fixtures/requests/roles.update.json',
				'Bearer ' . self::USER_TOKEN,
				StatusCodeInterface::STATUS_FORBIDDEN,
				__DIR__ . '/../../../fixtures/Middleware/responses/roles.index.forbidden.json',
			],
		];
	}

}
