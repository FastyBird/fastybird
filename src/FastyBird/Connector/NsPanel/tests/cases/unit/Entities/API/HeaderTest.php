<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Tests\Cases\Unit\Entities\API;

use Error;
use FastyBird\Connector\NsPanel\API\Messages\Header;
use FastyBird\Connector\NsPanel\Tests;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use Nette;
use Orisai\ObjectMapper;
use Ramsey\Uuid;
use function assert;

final class HeaderTest extends Tests\Cases\Unit\BaseTestCase
{

	/**
	 * @throws ApplicationExceptions\InvalidArgument
	 * @throws Nette\DI\MissingServiceException
	 * @throws ObjectMapper\Exception\InvalidData
	 * @throws Error
	 */
	public function testCreateMessage(): void
	{
		$container = $this->createContainer();

		$processor = $container->getByType(ObjectMapper\Processing\Processor::class);
		assert($processor instanceof ObjectMapper\Processing\DefaultProcessor);

		$id = Uuid\Uuid::uuid4();

		$message = $processor->process(
			[
				'name' => Types\Header::ERROR_RESPONSE,
				'message_id' => $id->toString(),
				'version' => '1',
			],
			Header::class,
		);

		self::assertSame(Types\Header::ERROR_RESPONSE, $message->getName()->getValue());
		self::assertSame($id->toString(), $message->getMessageId());
		self::assertSame('1', $message->getVersion());
	}

}
