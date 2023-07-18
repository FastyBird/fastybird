<?php declare(strict_types = 1);

/**
 * ReportDeviceStatusEventPayload.php
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
use Nette;
use stdClass;

/**
 * Report third-party device status to NS Panel event payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ReportDeviceStatusEventPayload implements Entities\API\Entity
{

	use Nette\SmartObject;

	public function __construct(private readonly Entities\API\Statuses\Status $state)
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
			'state' => $this->getState()->toArray(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->state = $this->getState()->toJson();

		return $json;
	}

}
