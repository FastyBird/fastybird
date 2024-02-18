<?php declare(strict_types = 1);

/**
 * SetDeviceStateEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper\Rules\BackedEnumValue;
use stdClass;

/**
 * Gateway report set device state event payload response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class SetDeviceStateEventPayload implements API\Messages\Message
{

	public function __construct(
		#[BackedEnumValue(class: Types\ServerStatus::class)]
		private Types\ServerStatus $type,
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
			'type' => $this->getType()->value,
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->type = $this->getType()->value;

		return $json;
	}

}
