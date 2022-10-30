<?php declare(strict_types = 1);

/**
 * Exchange.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          0.1.0
 *
 * @date           28.10.22
 */

namespace FastyBird\Module\Devices\Subscribers;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Events;
use Psr\Log;
use Symfony\Component\EventDispatcher;

/**
 * Exchange subscriber
 *
 * @package         FastyBird:DevicesModule!
 * @subpackage      Subscribers
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Exchange implements EventDispatcher\EventSubscriberInterface
{

	private Log\LoggerInterface $logger;

	public function __construct(Log\LoggerInterface|null $logger = null)
	{
		$this->logger = $logger ?? new Log\NullLogger();
	}

	public static function getSubscribedEvents(): array
	{
		return [
			Events\ExchangeStartup::class => 'startup',
		];
	}

	public function startup(): void
	{
		$this->logger->debug(
			'Registering exchange consumer',
			[
				'source' => MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES,
				'type' => 'subscriber',
			],
		);
	}

}
