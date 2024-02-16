<?php declare(strict_types = 1);

/**
 * ExternalPowerStateBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           21.12.23
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 2 device external power state message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class ExternalPowerStateBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\BoolValue()]
		private bool $present,
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

	/**
	 * @return array<string, bool>
	 */
	public function toState(): array
	{
		return [
			'external_present' => $this->isPresent(),
		];
	}

}
