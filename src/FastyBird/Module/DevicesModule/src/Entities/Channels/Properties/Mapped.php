<?php declare(strict_types = 1);

/**
 * Mapped.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          0.47.0
 *
 * @date           02.04.22
 */

namespace FastyBird\DevicesModule\Entities\Channels\Properties;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\DevicesModule\Utilities;
use FastyBird\Metadata\Types as MetadataTypes;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 */
class Mapped extends Property
{

	public function __construct(
		Entities\Channels\Channel $channel,
		Entities\Channels\Properties\Property $parent,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($channel, $identifier, $id);

		$this->parent = $parent;
	}

	public function getType(): MetadataTypes\PropertyType
	{
		return MetadataTypes\PropertyType::get(MetadataTypes\PropertyType::TYPE_MAPPED);
	}

	/**
	 * @throws Exceptions\InvalidState
	 */
	public function getParent(): Property
	{
		if ($this->parent === null) {
			throw new Exceptions\InvalidState('Mapped property can\'t be without parent property');
		}

		return $this->parent;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		if ($this->getParent() instanceof Entities\Channels\Properties\Variable) {
			return array_merge(parent::toArray(), [
				'default' => Utilities\ValueHelper::flattenValue($this->getDefault()),
				'value' => Utilities\ValueHelper::flattenValue($this->getValue()),
			]);
		}

		return parent::toArray();
	}

}
