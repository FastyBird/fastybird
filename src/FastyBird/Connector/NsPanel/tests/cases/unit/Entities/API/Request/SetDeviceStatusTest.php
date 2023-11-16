<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Entities\API\Request;

use Error;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;
use Nette\Utils;
use Orisai\ObjectMapper;
use function assert;

final class SetDeviceStatusTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\IOException
	 * @throws ObjectMapper\Exception\InvalidData
	 * @throws Utils\JsonException
	 * @throws Error
	 */
	public function testCreateEntity(): void
	{
		$container = $this->createContainer();

		$processor = $container->getByType(ObjectMapper\Processing\Processor::class);
		assert($processor instanceof ObjectMapper\Processing\DefaultProcessor);

		$entity = $processor->process(
			Utils\Json::decode(
				Utils\FileSystem::read(
					__DIR__ . '/../../../../../fixtures/Entities/API/Request/set_device_state.json',
				),
				Utils\Json::FORCE_ARRAY,
			),
			Entities\API\Request\SetDeviceState::class,
		);

		self::assertSame(
			'c2e2a418-c70c-4363-aa33-8f2de5196bda',
			$entity->getDirective()->getEndpoint()->getSerialNumber(),
		);
	}

}
