<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Fig\Http\Message\RequestMethodInterface;
use IPub\SlimRouter;
use IPub\SlimRouter\Http as SlimRouterHttp;
use React\Http\Message\ServerRequest;
use Tester\Assert;
use Tests\Tools;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class DevicesV1ControllerTest extends DbTestCase
{

	/**
	 * @param string $url
	 * @param string|null $token
	 * @param int $statusCode
	 * @param string $fixture
	 *
	 * @dataProvider ./../../../fixtures/Controllers/devicesRead.php
	 */
	public function testRead(string $url, ?string $token, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers
		);

		$response = $router->handle($request);

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody()
		);
		Assert::same($statusCode, $response->getStatusCode());
		Assert::type(SlimRouterHttp\Response::class, $response);
	}

	/**
	 * @param string $url
	 * @param string|null $token
	 * @param string $body
	 * @param int $statusCode
	 * @param string $fixture
	 * @param string|null $secondUrl
	 * @param string|null $secondToken
	 * @param int|null $secondStatusCode
	 * @param string|null $secondFixture
	 *
	 * @dataProvider ./../../../fixtures/Controllers/devicesCreate.php
	 */
	public function testCreate(
		string $url,
		?string $token,
		string $body,
		int $statusCode,
		string $fixture,
		?string $secondUrl = null,
		?string $secondToken = null,
		?int $secondStatusCode = null,
		?string $secondFixture = null
	): void {
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_POST,
			$url,
			$headers,
			$body
		);

		$response = $router->handle($request);

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody()
		);
		Assert::same($statusCode, $response->getStatusCode());
		Assert::type(SlimRouterHttp\Response::class, $response);

		if ($secondUrl !== null && $secondStatusCode !== null && $secondFixture !== null) {
			$headers = [];

			if ($secondToken !== null) {
				$headers['authorization'] = $secondToken;
			}

			$request = new ServerRequest(
				RequestMethodInterface::METHOD_GET,
				$secondUrl,
				$headers
			);

			$response = $router->handle($request);

			Tools\JsonAssert::assertFixtureMatch(
				$secondFixture,
				(string) $response->getBody()
			);
			Assert::same($secondStatusCode, $response->getStatusCode());
			Assert::type(SlimRouterHttp\Response::class, $response);
		}
	}

	/**
	 * @param string $url
	 * @param string|null $token
	 * @param string $body
	 * @param int $statusCode
	 * @param string $fixture
	 *
	 * @dataProvider ./../../../fixtures/Controllers/devicesUpdate.php
	 */
	public function testUpdate(string $url, ?string $token, string $body, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_PATCH,
			$url,
			$headers,
			$body
		);

		$response = $router->handle($request);

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody()
		);
		Assert::same($statusCode, $response->getStatusCode());
		Assert::type(SlimRouterHttp\Response::class, $response);
	}

	/**
	 * @param string $url
	 * @param string|null $token
	 * @param int $statusCode
	 * @param string $fixture
	 *
	 * @dataProvider ./../../../fixtures/Controllers/devicesDelete.php
	 */
	public function testDelete(string $url, ?string $token, int $statusCode, string $fixture): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [];

		if ($token !== null) {
			$headers['authorization'] = $token;
		}

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_DELETE,
			$url,
			$headers
		);

		$response = $router->handle($request);

		Tools\JsonAssert::assertFixtureMatch(
			$fixture,
			(string) $response->getBody()
		);
		Assert::same($statusCode, $response->getStatusCode());
		Assert::type(SlimRouterHttp\Response::class, $response);
	}

}

$test_case = new DevicesV1ControllerTest();
$test_case->run();
