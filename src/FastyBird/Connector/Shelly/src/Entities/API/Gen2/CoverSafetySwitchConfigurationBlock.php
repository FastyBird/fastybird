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
use Orisai\ObjectMapper;

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
		#[ObjectMapper\Rules\BoolValue()]
		private readonly bool $enable,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['open', 'closed', 'both'])]
		private readonly string $direction,
		#[ObjectMapper\Rules\ArrayEnumValue(cases: ['stop', 'reverse', 'pause'])]
		private readonly string $action,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\ArrayEnumValue(cases: ['reverse']),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('allowed_move')]
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
