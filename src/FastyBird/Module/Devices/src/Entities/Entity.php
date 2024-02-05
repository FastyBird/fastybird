<?php declare(strict_types = 1);

/**
 * Transformer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Devices\Entities;

use FastyBird\Library\Metadata\Types as MetadataTypes;
use IPub\DoctrineCrud;
use Ramsey\Uuid;

/**
 * Base entity interface
 *
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Entity extends DoctrineCrud\Entities\IEntity
{

	public function getId(): Uuid\UuidInterface;

	public function getPlainId(): string;

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array;

	// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
	public function getSource(): MetadataTypes\ModuleSource|MetadataTypes\PluginSource|MetadataTypes\ConnectorSource|MetadataTypes\AddonSource|MetadataTypes\BridgeSource;

}
