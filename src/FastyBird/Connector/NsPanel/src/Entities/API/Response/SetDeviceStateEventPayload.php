<?php declare(strict_types = 1);

/**
 * SetDeviceStateEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Bootstrap\ObjectMapper as BootstrapObjectMapper;
use stdClass;

/**
 * Gateway report set device state event payload response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStateEventPayload implements Entities\API\Entity
{

	public function __construct(
		#[BootstrapObjectMapper\Rules\ConsistenceEnumValue(class: Types\ServerStatus::class)]
		private readonly Types\ServerStatus $type,
	)
	{
	}

	public function getType(): Types\ServerStatus
	{
		return $this->type;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'type' => $this->getType()->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->type = $this->getType()->getValue();

		return $json;
	}

}
