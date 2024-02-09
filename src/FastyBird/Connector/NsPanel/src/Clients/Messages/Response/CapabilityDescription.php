<?php declare(strict_types = 1);

/**
 * CapabilityDescription.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           09.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients\Messages\Response;

use FastyBird\Connector\NsPanel\Clients;
use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Application\ObjectMapper as ApplicationObjectMapper;
use Orisai\ObjectMapper;

/**
 * Device capability definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class CapabilityDescription implements Clients\Messages\Message
{

	public function __construct(
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\Capability::class)]
		private readonly Types\Capability $capability,
		#[ApplicationObjectMapper\Rules\ConsistenceEnumValue(class: Types\Permission::class)]
		private readonly Types\Permission $permission,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private readonly string|null $name = null,
	)
	{
	}

	public function getCapability(): Types\Capability
	{
		return $this->capability;
	}

	public function getPermission(): Types\Permission
	{
		return $this->permission;
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

}
