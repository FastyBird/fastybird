<?php declare(strict_types = 1);

/**
 * SetSubDeviceStatus.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           15.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Request;

use FastyBird\Connector\NsPanel\Entities;
use Nette;
use stdClass;

/**
 * Set NS Panel sub-device status request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SetSubDeviceStatus implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(
		private readonly Entities\API\Statuses\Status $state,
	)
	{
	}

	public function getState(): Entities\API\Statuses\Status
	{
		return $this->state;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'status' => $this->getState()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->status = $this->getState()->toJson();

		return $json;
	}

}
