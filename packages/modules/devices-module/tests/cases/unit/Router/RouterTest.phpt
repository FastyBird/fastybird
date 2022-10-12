<?php declare(strict_types = 1);

namespace Tests\Cases\Unit;

use Fig\Http\Message\RequestMethodInterface;
use IPub\SlimRouter;
use React\Http\Message\ServerRequest;
use Tester\Assert;

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../DbTestCase.php';

/**
 * @testCase
 */
final class RouterTest extends DbTestCase
{

	public function setUp(): void
	{
		$this->registerNeonConfigurationFile(__DIR__ . '/prefixedRoutes.neon');

		parent::setUp();
	}

	/**
	 * @param string $url
	 * @param string $token
	 * @param int $statusCode
	 *
	 * @dataProvider ./../../../fixtures/Routes/prefixedRoutes.php
	 */
	public function testPrefixedRoutes(string $url, string $token, int $statusCode): void
	{
		/** @var SlimRouter\Routing\IRouter $router */
		$router = $this->getContainer()->getByType(SlimRouter\Routing\IRouter::class);

		$headers = [
			'authorization' => $token,
		];

		$request = new ServerRequest(
			RequestMethodInterface::METHOD_GET,
			$url,
			$headers
		);

		$response = $router->handle($request);

		Assert::same($statusCode, $response->getStatusCode());
	}

}

$test_case = new RouterTest();
$test_case->run();
