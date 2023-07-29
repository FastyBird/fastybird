<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\DI;

use FastyBird\Plugin\RabbitMq\Channels;
use FastyBird\Plugin\RabbitMq\Connections;
use FastyBird\Plugin\RabbitMq\Handlers;
use FastyBird\Plugin\RabbitMq\Subscribers;
use FastyBird\Plugin\RabbitMq\Tests\Cases\Unit\BaseTestCase;
use FastyBird\Plugin\RabbitMq\Utilities;
use Nette;

final class RabbitMqExtensionTest extends BaseTestCase
{

	/**
	 * @throws Nette\DI\MissingServiceException
	 */
	public function XtestServicesRegistration(): void
	{
		self::assertNotNull($this->container->getByType(Connections\Connection::class, false));

		self::assertNotNull($this->container->getByType(Channels\Channel::class, false));
		self::assertNotNull($this->container->getByType(Channels\Factory::class, false));

		self::assertNotNull($this->container->getByType(Handlers\Message::class, false));

		self::assertNotNull($this->container->getByType(Subscribers\Channel::class, false));

		self::assertNotNull($this->container->getByType(Utilities\IdentifierGenerator::class, false));
	}

}
