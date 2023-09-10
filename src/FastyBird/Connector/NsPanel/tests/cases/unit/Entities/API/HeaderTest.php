<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Entities\API;

use Error;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\Exceptions as BootstrapExceptions;
use Nette;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function assert;

final class HeaderTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws BootstrapExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws ObjectMapper\Exception\InvalidData
	 * @throws Error
	 */
	public function testCreateEntity(): void
	{
		$container = $this->createContainer();

		$processor = $container->getByType(ObjectMapper\Processing\Processor::class);
		assert($processor instanceof ObjectMapper\Processing\DefaultProcessor);

		$id = Uuid\Uuid::uuid4();

		$entity = $processor->process(
			[
				'name' => Types\Header::ERROR_RESPONSE,
				'message_id' => $id->toString(),
				'version' => '1',
			],
			Entities\API\Header::class,
		);

		self::assertSame(Types\Header::ERROR_RESPONSE, $entity->getName()->getValue());
		self::assertSame($id->toString(), $entity->getMessageId());
		self::assertSame('1', $entity->getVersion());
	}

}
