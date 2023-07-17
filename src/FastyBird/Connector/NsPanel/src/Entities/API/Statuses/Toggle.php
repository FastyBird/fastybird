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

use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Connector\NsPanel\Types;
use Nette;
use stdClass;

/**
 * Toggle control capability state
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class Toggle implements Status
{

	use Nette\SmartObject;

	public function __construct(
		private readonly string $name,
		private readonly Types\TogglePayload $value,
		private readonly Types\StartupPayload|null $startup = null,
	)
	{
	}

	public function getType(): Types\Capability
	{
		return Types\Capability::get(Types\Capability::TOGGLE);
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): Types\TogglePayload
	{
		return $this->value;
	}

	public function getStartup(): Types\StartupPayload|null
	{
		return $this->startup;
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
		$json->{Types\Capability::TOGGLE} = new stdClass();
		$json->{Types\Capability::TOGGLE}->{$this->getName()} = new stdClass();
		$json->{Types\Capability::TOGGLE}->{$this->getName()}->toggleState = $this->getValue()->getValue();

		if ($this->getStartup() !== null) {
			$json->{Types\Capability::TOGGLE}->{$this->getName()}->startup = $this->getStartup()->getValue();
		}

		return $json;
	}

}
