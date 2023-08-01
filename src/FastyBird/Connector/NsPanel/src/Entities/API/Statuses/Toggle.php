<?php declare(strict_types = 1);

/**
 * Toggle.php
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

namespace FastyBird\Connector\NsPanel\Entities\API\Statuses;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use stdClass;

/**
 * Toggle control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Toggle implements Status, ObjectMapper\MappedObject
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $toggleState,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\DefaultValue(null)]
		private readonly string|null $startup = null,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::TOGGLE);
	}

	public function getName(): string|null
	{
		return null;
	}

	public function getValue(): Types\TogglePayload
	{
		return Types\TogglePayload::get($this->toggleState);
	}

	public function getStartup(): Types\StartupPayload|null
	{
		return $this->startup !== null ? Types\StartupPayload::get($this->startup) : null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->getName(),
			'value' => $this->getValue()->getValue(),
			'startup' => $this->getStartup()?->getValue(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->toggleState = $this->getValue()->getValue();

		if ($this->getStartup() !== null) {
			$json->startup = $this->getStartup()->getValue();
		}

		return $json;
	}

}
