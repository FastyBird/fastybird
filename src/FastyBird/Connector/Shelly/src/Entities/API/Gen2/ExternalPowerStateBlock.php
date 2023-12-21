<?php declare(strict_types = 1);

/**
 * ExternalPowerStateBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           21.12.23
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;
use Orisai\ObjectMapper;

/**
 * Generation 2 device external power state entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ExternalPowerStateBlock implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $present,
	)
	{
	}

	public function isPresent(): bool
	{
		return $this->present;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'present' => $this->isPresent(),
		];
	}

}
