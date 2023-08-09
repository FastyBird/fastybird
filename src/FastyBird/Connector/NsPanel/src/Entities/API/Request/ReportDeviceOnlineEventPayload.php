<?php declare(strict_types = 1);

/**
 * ReportDeviceOnlineEventPayload.php
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
 * Report third-party device online state to NS Panel event payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ReportDeviceOnlineEventPayload implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $online,
	)
	{
	}

	public function isOnline(): bool
	{
		return $this->online;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'online' => $this->isOnline(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->online = $this->isOnline();

		return $json;
	}

}
