<?php declare(strict_types = 1);

/**
 * CoverSafetySwitchConfigurationBlock.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           27.12.22
 */

namespace FastyBird\Connector\Shelly\Entities\API\Gen2;

use FastyBird\Connector\Shelly\Entities;

/**
 * Generation 2 device cover component safety switch configuration entity
 *
 * @package        FastyBird:ShellyConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CoverSafetySwitchConfigurationBlock implements Entities\API\Entity
{

	public function __construct(
		private readonly bool $enable,
		private readonly string $direction,
		private readonly string $action,
		private readonly string|null $allowedMove,
	)
	{
	}

	public function isEnabled(): bool
	{
		return $this->enable;
	}

	public function getDirection(): string
	{
		return $this->direction;
	}

	public function getAction(): string
	{
		return $this->action;
	}

	public function getAllowedMove(): string|null
	{
		return $this->allowedMove;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'enabled' => $this->isEnabled(),
			'direction' => $this->getDirection(),
			'action' => $this->getAction(),
			'allowed_move' => $this->getAllowedMove(),
		];
	}

}
