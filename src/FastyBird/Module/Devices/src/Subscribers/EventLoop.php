<?php declare(strict_types = 1);

/**
 * EventLoop.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           11.09.24
 */

namespace FastyBird\Module\Devices\Subscribers;

use FastyBird\Library\Application\Events as ApplicationEvents;
use FastyBird\Module\Devices\Utilities;
use Nette;
use Symfony\Component\EventDispatcher;

/**
 * Event loop events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class EventLoop implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(private readonly Utilities\EventLoopStatus $eventLoopStatus)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents\EventLoopStarted::class => 'loopStarted',
			ApplicationEvents\EventLoopStopped::class => 'loopStopped',
			ApplicationEvents\EventLoopStopping::class => 'loopStopped',
		];
	}

	public function loopStarted(): void
	{
		$this->eventLoopStatus->setStatus(true);
	}

	public function loopStopped(): void
	{
		$this->eventLoopStatus->setStatus(false);
	}

}
