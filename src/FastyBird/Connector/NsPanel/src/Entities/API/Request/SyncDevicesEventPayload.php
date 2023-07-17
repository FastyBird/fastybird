<?php declare(strict_types = 1);

/**
 * SyncDevicesEventPayload.php
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
use function array_map;

/**
 * Synchronise third-party devices with NS Panel event payload request definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class SyncDevicesEventPayload implements Entities\API\Entity
{

	use Nette\SmartObject;

	/**
	 * @param array<Entities\API\ThirdPartyDevice> $endpoints
	 */
	public function __construct(private readonly array $endpoints)
	{
	}

	/**
	 * @return array<Entities\API\ThirdPartyDevice>
	 */
	public function getEndpoints(): array
	{
		return $this->endpoints;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'endpoints' => array_map(
				static fn (Entities\API\ThirdPartyDevice $description): array => $description->toArray(),
				$this->getEndpoints(),
			),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->endpoints = array_map(
			static fn (Entities\API\ThirdPartyDevice $description): object => $description->toJson(),
			$this->getEndpoints(),
		);

		return $json;
	}

}
