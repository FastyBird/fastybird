<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           24.02.20
 */

namespace FastyBird\FbMqttConnector\Entities\Messages;

use FastyBird\FbMqttConnector;
use Ramsey\Uuid;
use SplObjectStorage;
use function array_merge;

/**
 * Device or channel property
 *
 * @package        FastyBird:FbMqttConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Property extends Entity
{

	private string|null $value = FbMqttConnector\Constants::VALUE_NOT_SET;

	/** @var SplObjectStorage<PropertyAttribute, null> */
	private SplObjectStorage $attributes;

	public function __construct(
		Uuid\UuidInterface $connector,
		string $device,
		private readonly string $property,
	)
	{
		parent::__construct($connector, $device);

		$this->attributes = new SplObjectStorage();
	}

	public function addAttribute(PropertyAttribute $attribute): void
	{
		if (!$this->attributes->contains($attribute)) {
			$this->attributes->attach($attribute);
		}
	}

	public function getProperty(): string
	{
		return $this->property;
	}

	/**
	 * @return Array<PropertyAttribute>
	 */
	public function getAttributes(): array
	{
		$data = [];

		foreach ($this->attributes as $item) {
			$data[] = $item;
		}

		return $data;
	}

	public function getValue(): string|null
	{
		return $this->value;
	}

	public function setValue(string $value): void
	{
		$this->value = $value;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		$return = array_merge([
			'property' => $this->getProperty(),
		], parent::toArray());

		foreach ($this->getAttributes() as $attribute) {
			$return[$attribute->getAttribute()] = $attribute->getValue();
		}

		if ($this->getValue() !== FbMqttConnector\Constants::VALUE_NOT_SET) {
			$return['value'] = $this->getValue();
		}

		return $return;
	}

}
