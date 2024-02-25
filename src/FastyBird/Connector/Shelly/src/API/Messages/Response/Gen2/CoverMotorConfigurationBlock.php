<?php declare(strict_types = 1);

/**
 * CoverMotorConfigurationBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use Orisai\ObjectMapper;

/**
 * Generation 2 device cover component motor configuration message
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final readonly class CoverMotorConfigurationBlock implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('idle_power_thr')]
		private float $idlePowerThreshold,
		#[ObjectMapper\Rules\FloatValue()]
		#[ObjectMapper\Modifiers\FieldName('idle_confirm_period')]
		private float $idleConfirmPeriod,
	)
	{
	}

	public function getIdlePowerThreshold(): float
	{
		return $this->idlePowerThreshold;
	}

	public function getIdleConfirmPeriod(): float
	{
		return $this->idleConfirmPeriod;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'idle_power_threshold' => $this->getIdlePowerThreshold(),
			'idle_confirm_period' => $this->getIdleConfirmPeriod(),
		];
	}

}
