<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\Tests\Cases\Unit\API;

use Error;
use FastyBird\Connector\Tuya\API;
use FastyBird\Connector\Tuya\Exceptions;
use FastyBird\Connector\Tuya\Tests;
use FastyBird\Connector\Tuya\Types;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use GuzzleHttp;
use Nette\DI;
use Nette\Utils;
use Psr\Http;
use RuntimeException;
use function strval;

final class OpenApiTest extends Tests\Cases\Unit\DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\OpenApiCall
	 * @throws Exceptions\OpenApiError
	 * @throws RuntimeException
	 * @throws Error
	 */
	public function testGetUserDevices(): void
	{
		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('send')
			->willReturnCallback(
				function (Http\Message\RequestInterface $request): Http\Message\ResponseInterface {
					$responseBody = $this->createMock(Http\Message\StreamInterface::class);
					$responseBody
						->method('rewind');

					if (strval($request->getUri()) === 'https://openapi.tuyaeu.com/v1.0/users/userid123/devices') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/get_user_devices.json',
								),
							);
					} elseif (strval($request->getUri()) === 'http://10.10.0.10:55000/nrc/sdd_0.xml') {
						$responseBody
							->method('getContents')
							->willReturn(
								Utils\FileSystem::read(
									__DIR__ . '/../../../fixtures/API/response/crypto_check.xml',
								),
							);
					}

					$response = $this->createMock(Http\Message\ResponseInterface::class);
					$response
						->method('getBody')
						->willReturn($responseBody);

					return $response;
				},
			);

		$httpClientFactory = $this->createMock(API\HttpClientFactory::class);
		$httpClientFactory
			->method('create')
			->willReturnCallback(
				static function (bool $async) use ($httpClient) {
					self::assertFalse($async);

					return $httpClient;
				},
			);

		$this->mockContainerService(
			API\HttpClientFactory::class,
			$httpClientFactory,
		);

		$openApiFactory = $this->getContainer()->getByType(API\OpenApiFactory::class);

		$openApi = $openApiFactory->create(
			'testing-connector',
			'abcdefghijklmn',
			'opqrstuvwxyz',
			Types\OpenApiEndpoint::get(Types\OpenApiEndpoint::EUROPE),
		);

		$userDevices = $openApi->getUserDevices('userid123', false);

		self::assertCount(4, $userDevices->getResult());
		self::assertSame([
			'result' => [
				0 => [
					'id' => 'bf3e9d85a52b163f940wgx',
					'name' => 'Wall socket - outdoor',
					'uid' => 'eu1638804163882cLR8v',
					'local_key' => 'fea74f634dc369c1',
					'category' => 'cz',
					'product_id' => 'pnzfdr9y',
					'product_name' => 'Outdoor Socket Adapter',
					'sub' => true,
					'uuid' => '9035eafffeb8f501',
					'owner_id' => '44154302',
					'online' => true,
					'status' => [
						0 => [
							'code' => 'switch_1',
							'value' => false,
							'type' => null,
						],
						1 => [
							'code' => 'countdown_1',
							'value' => 0.0,
							'type' => null,
						],
					],
					'active_time' => 1669988230,
					'create_time' => 1669988230,
					'update_time' => 1692389367,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay1559701439060fw6BY/90009dc671abcfa1541a9606196ef896.png',
					'ip' => null,
					'time_zone' => '+01:00',
				],
				1 => [
					'id' => 'bfa1a65b1d7f75a9aenvkc',
					'name' => 'NEO Gateway',
					'uid' => 'eu1638804163882cLR8v',
					'local_key' => 'fea74f634dc369c1',
					'category' => 'wg2',
					'product_id' => 'be9fookuobd9w8z3',
					'product_name' => 'NEO Gateway',
					'sub' => false,
					'uuid' => '52f41514cef56dbb',
					'owner_id' => '44154302',
					'online' => true,
					'status' => [],
					'active_time' => 1669643113,
					'create_time' => 1669392668,
					'update_time' => 1692385165,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay1503986080106Gppjy/1d241ba5f7e7b5139ee6bbf2b0eeb11b.png',
					'ip' => '85.160.10.238',
					'time_zone' => '+01:00',
				],
				2 => [
					'id' => 'bfa51eb7b64c2f5eedradw',
					'name' => 'Living room environment',
					'uid' => 'eu1638804163882cLR8v',
					'local_key' => 'fea74f634dc369c1',
					'category' => 'ldcg',
					'product_id' => 'ftdkanlj',
					'product_name' => 'Luminance sensor',
					'sub' => true,
					'uuid' => '5c0272fffe037960',
					'owner_id' => '44154302',
					'online' => true,
					'status' => [
						0 => [
							'code' => 'bright_value',
							'value' => 0.0,
							'type' => null,
						],
						1 => [
							'code' => 'battery_percentage',
							'value' => 93.0,
							'type' => null,
						],
						2 => [
							'code' => 'temp_current',
							'value' => 281.0,
							'type' => null,
						],
						3 => [
							'code' => 'humidity_value',
							'value' => 518.0,
							'type' => null,
						],
						4 => [
							'code' => 'bright_sensitivity',
							'value' => 10.0,
							'type' => null,
						],
					],
					'active_time' => 1669643114,
					'create_time' => 1669393353,
					'update_time' => 1692235573,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay15327721968035jwx9/9ef66b23e59bd8a8c4da13536be92eb6.png',
					'ip' => null,
					'time_zone' => '+01:00',
				],
				3 => [
					'id' => '402675772462ab280dae',
					'name' => 'WiFi Smart Timer',
					'uid' => 'eu1638804163882cLR8v',
					'local_key' => '19a61d30d285f6f2',
					'category' => 'kg',
					'product_id' => 'SJet14RibkVEZDOB',
					'product_name' => 'WiFi Smart Timer',
					'sub' => false,
					'uuid' => '402675772462ab280dae',
					'owner_id' => '44154302',
					'online' => false,
					'status' => [
						0 => [
							'code' => 'switch',
							'value' => true,
							'type' => null,
						],
						1 => [
							'code' => 'countdown_1',
							'value' => 0.0,
							'type' => null,
						],
					],
					'active_time' => 1640898671,
					'create_time' => 1594720605,
					'update_time' => 1671125276,
					'biz_type' => 0,
					'icon' => 'smart/icon/ay1522655691209YPydg/15601526771def4a0a3e0.jpg',
					'ip' => '80.78.136.56',
					'time_zone' => '+01:00',
				],
			],
		], $userDevices->toArray());
	}

}
