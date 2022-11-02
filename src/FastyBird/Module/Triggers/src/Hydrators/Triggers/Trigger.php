<?php declare(strict_types = 1);

/**
 * TriggerHydrator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Hydrators
 * @since          0.1.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Module\Triggers\Hydrators\Triggers;

use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Schemas;
use IPub\JsonAPIDocument;

/**
 * Trigger entity hydrator
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Hydrators
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-template TEntityClass of Entities\Triggers\ITrigger
 * @phpstan-extends  JsonApiHydrators\Hydrator<TEntityClass>
 */
abstract class TriggerHydrator extends JsonApiHydrators\Hydrator
{

	/** @var string[] */
	protected array $attributes = [
		'name',
		'comment',
		'enabled',
	];

	/** @var string[] */
	protected array $relationships = [
		Schemas\Triggers\TriggerSchema::RELATIONSHIPS_ACTIONS,
		Schemas\Triggers\TriggerSchema::RELATIONSHIPS_NOTIFICATIONS,
	];

	/**
	 * @param JsonAPIDocument\Objects\IStandardObject $attributes
	 *
	 * @return bool
	 */
	protected function hydrateEnabledAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): bool
	{
		return is_scalar($attributes->get('enabled')) && (bool) $attributes->get('enabled');
	}

}
