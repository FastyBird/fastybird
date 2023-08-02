<?php declare(strict_types = 1);

/**
 * SetDeviceStatusDirectivePayload.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel requested set device status directive payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetDeviceStatusDirectivePayload implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\MappedObjectValue(Entities\API\State::class)]
		private readonly Entities\API\State $state,
	)
	{
	}

	/**
	 * @return array<Entities\API\Statuses\Status>
	 */
	public function getState(): array
	{
		return $this->state->getStatuses();
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'state' => $this->state->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->state = $this->state->toJson();

		return $json;
	}

}
