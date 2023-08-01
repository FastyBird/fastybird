<?php declare(strict_types = 1);

/**
 * Capability.php
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

namespace FastyBird\Connector\NsPanel\Entities\API;

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Device capability definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Capability implements Entities\API\Entity, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $capability,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $permission,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\DefaultValue(null)]
		private readonly string|null $name = null,
	)
	{
	}

	public function getCapability(): Types\Capability
	{
		return Types\Capability::get($this->capability);
	}

	public function getPermission(): Types\Permission
	{
		return Types\Permission::get($this->permission);
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'capability' => $this->getCapability()->getValue(),
			'permission' => $this->getPermission()->getValue(),
			'name' => $this->getName(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->capability = $this->getCapability()->getValue();
		$json->permission = $this->getPermission()->getValue();

		if ($this->getCapability()->equalsValue(Types\Capability::TOGGLE) && $this->getName() !== null) {
			$json->name = $this->getName();
		}

		return $json;
	}

}
