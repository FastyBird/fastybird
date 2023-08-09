<?php declare(strict_types = 1);

/**
 * AfterConnectorExecutionStart.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 * @since          1.0.0
 *
 * @date           22.06.22
 */

namespace FastyBird\Module\Devices\Events;

use FastyBird\Module\Devices\Entities;
use Symfony\Contracts\EventDispatcher;

/**
 * Event fired after connector has been started
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Events
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class AfterConnectorExecutionStart extends EventDispatcher\Event
{

	public function __construct(private readonly Entities\Connectors\Connector $connector)
	{
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		return $this->connector;
	}

}
