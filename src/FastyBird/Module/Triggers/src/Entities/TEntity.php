<?php declare(strict_types = 1);

/**
 * TEntity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           25.05.20
 */

namespace FastyBird\TriggersModule\Entities;

use FastyBird\Metadata\Types as MetadataTypes;
use Ramsey\Uuid;

/**
 * Entity base trait
 *
 * @package        FastyBird:TriggersModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @property-read Uuid\UuidInterface $id
 */
trait TEntity
{

	/**
	 * @return Uuid\UuidInterface
	 */
	public function getId(): Uuid\UuidInterface
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getPlainId(): string
	{
		return $this->id->toString();
	}

	/**
	 * @return MetadataTypes\ModuleSourceType|MetadataTypes\ConnectorSourceType|MetadataTypes\PluginSourceType
	 */
	public function getSource()
	{
		return MetadataTypes\ModuleSourceType::get(MetadataTypes\ModuleSourceType::SOURCE_MODULE_TRIGGERS);
	}

}
