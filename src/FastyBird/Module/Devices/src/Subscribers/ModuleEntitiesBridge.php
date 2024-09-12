<?php declare(strict_types = 1);

/**
 * ModuleEntitiesBridge.php
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
use Nette;
use Symfony\Component\EventDispatcher;

/**
 * Doctrine entities events
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Subscribers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ModuleEntitiesBridge implements EventDispatcher\EventSubscriberInterface
{

	use Nette\SmartObject;

	public function __construct(private readonly ModuleEntities $subscriber)
	{
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ApplicationEvents\EventLoopStarted::class => 'enableAsync',
			ApplicationEvents\EventLoopStopped::class => 'disableAsync',
			ApplicationEvents\EventLoopStopping::class => 'disableAsync',
			ApplicationEvents\DbTransactionFinished::class => 'transactionFinished',
		];
	}

	public function enableAsync(): void
	{
		$this->subscriber->enableAsync();
	}

	public function disableAsync(): void
	{
		$this->subscriber->enableAsync();
	}

	public function transactionFinished(): void
	{
		$this->subscriber->transactionFinished();
	}

}
