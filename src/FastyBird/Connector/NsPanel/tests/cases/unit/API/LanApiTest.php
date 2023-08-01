<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\API;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queries;
use FastyBird\Connector\NsPanel\Tests\Cases\Unit\DbTestCase;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Exceptions as DevicesExceptions;
use FastyBird\Module\Devices\Models as DevicesModels;
use GuzzleHttp;
use Nette\DI;
use Nette\Utils;
use Psr\Http;
use RuntimeException;

final class LanApiTest extends DbTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws DevicesExceptions\InvalidState
	 * @throws DI\MissingServiceException
	 * @throws Exceptions\InvalidArgument
	 * @throws Exceptions\InvalidState
	 * @throws MetadataExceptions\InvalidState
	 * @throws RuntimeException
	 * @throws Utils\JsonException
	 */
	public function testGetSubDevices(): void
	{
		$responseBody = $this->createMock(Http\Message\StreamInterface::class);
		$responseBody
			->method('getContents')
			->willReturn(Utils\FileSystem::read(__DIR__ . '/../../../fixtures/Clients/responses/get_sub_devices.json'));

		$response = $this->createMock(Http\Message\ResponseInterface::class);
		$response
			->method('getBody')
			->willReturn($responseBody);

		$httpClient = $this->createMock(GuzzleHttp\Client::class);
		$httpClient
			->method('request')
			->willReturn($response);

		$httpClientFactory = $this->createMock(API\HttpClientFactory::class);
		$httpClientFactory
			->method('createClient')
			->willReturn($httpClient);

		$this->mockContainerService(
			API\HttpClientFactory::class,
			$httpClientFactory,
		);

		$connectorsRepository = $this->getContainer()->getByType(DevicesModels\Connectors\ConnectorsRepository::class);

		$findConnectorQuery = new Queries\FindConnectors();
		$findConnectorQuery->byIdentifier('ns-panel');

		$connector = $connectorsRepository->findOneBy($findConnectorQuery, Entities\NsPanelConnector::class);
		self::assertInstanceOf(Entities\NsPanelConnector::class, $connector);

		$lanApiFactory = $this->getContainer()->getByType(API\LanApiFactory::class);

		$lanApi = $lanApiFactory->create($connector->getIdentifier());

		$subDevices = $lanApi->getSubDevices(
			'127.0.0.1',
			'abcdefghijklmnopqrstuvwxyz',
			API\LanApi::GATEWAY_PORT,
			false,
		);

		self::assertCount(2, $subDevices->getData()->getDevicesList());

		foreach ($subDevices->getData()->getDevicesList() as $subDevice) {
			self::assertTrue($subDevice->getStatuses() !== []);
		}

		self::assertJsonStringEqualsJsonFile(
			__DIR__ . '/../../../fixtures/Clients/responses/get_sub_devices.json',
			Utils\Json::encode($subDevices->toJson()),
		);
	}

}
