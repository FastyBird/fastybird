<?php declare(strict_types = 1);

namespace FastyBird\Connector\Zigbee2Mqtt\Tests\Tools;

use DateTimeInterface;
use FastyBird\DateTimeFactory;
use IPub\DoctrineTimestampable\Providers as DoctrineTimestampableProviders;
use Nette\DI;

readonly class DateTimeProvider implements DoctrineTimestampableProviders\DateProvider
{

	public function __construct(private DI\Container $container)
	{
	}

	public function getDate(): DateTimeInterface
	{
		return $this->container->getByType(DateTimeFactory\Factory::class)->getNow();
	}

	public function getTimestamp(): int
	{
		return $this->container->getByType(DateTimeFactory\Factory::class)->getNow()->getTimestamp();
	}

}
