<?php declare(strict_types = 1);

/**
 * ConnectorPropertyStateEntityDeleted.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           29.07.23
 */

namespace FastyBird\Module\Devices\Events;

use Ramsey\Uuid;
use Symfony\Contracts\EventDispatcher;

/**
 * Connector property state entity was deleted event
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ConnectorPropertyStateEntityDeleted extends EventDispatcher\Event
{

	public function __construct(private readonly Uuid\UuidInterface $id)
	{
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->id;
	}

}
