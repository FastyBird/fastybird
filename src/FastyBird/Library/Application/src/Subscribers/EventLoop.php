<?php declare(strict_types = 1);

/**
 * EventLoop.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApplicationLibrary!
 * @subpackage     Subscribers
 * @since          1.0.0
 *
 * @date           11.09.24
 */

namespace FastyBird\Library\Application\Subscribers;

use FastyBird\Library\Application\Events;
use FastyBird\Library\Application\Utilities;
use Nette;
use Symfony\Component\EventDispatcher;

/**
 * Event loop events
 *
 * @package        FastyBird:ApplicationLibrary!
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
			Events\EventLoopStarted::class => 'loopStarted',
			Events\EventLoopStopped::class => 'loopStopped',
			Events\EventLoopStopping::class => 'loopStopped',
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
