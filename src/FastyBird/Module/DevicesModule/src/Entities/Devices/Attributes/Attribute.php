<?php declare(strict_types = 1);

/**
 * Attribute.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          0.57.0
 *
 * @date           22.04.22
 */

namespace FastyBird\DevicesModule\Entities\Devices\Attributes;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\DevicesModule\Entities;
use FastyBird\DevicesModule\Exceptions;
use FastyBird\Metadata\Types as MetadataTypes;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Nette\Utils;
use Ramsey\Uuid;
use function implode;
use function is_scalar;
use function is_string;
use function preg_match;
use function str_replace;
use function str_split;
use function strval;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_devices_attributes",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Devices attributes"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="attribute_identifier_unique", columns={"attribute_identifier", "device_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="attribute_identifier_idx", columns={"attribute_identifier"}),
 *       @ORM\Index(name="attribute_name_idx", columns={"attribute_name"}),
 *       @ORM\Index(name="attribute_content_idx", columns={"attribute_content"})
 *     }
 * )
 */
class Attribute implements Entities\Entity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use Entities\TEntity;
	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="attribute_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\Column(type="string", name="attribute_identifier", length=50, nullable=false)
	 */
	protected string $identifier;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="attribute_name", nullable=true, options={"default": null})
	 */
	protected string|null $name = null;

	/**
	 * @IPubDoctrine\Crud(is="writable")
	 * @ORM\Column(type="string", name="attribute_content", nullable=true, options={"default": null})
	 */
	protected string|null $content = null;

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\DevicesModule\Entities\Devices\Device", inversedBy="attributes")
	 * @ORM\JoinColumn(name="device_id", referencedColumnName="device_id", onDelete="CASCADE", nullable=false)
	 */
	private Entities\Devices\Device $device;

	public function __construct(string $identifier, Entities\Devices\Device $device)
	{
		$this->id = Uuid\Uuid::uuid4();

		$this->identifier = $identifier;
		$this->device = $device;

		$device->addAttribute($this);
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function setName(string|null $name): void
	{
		$this->name = $name;
	}

	public function getContent(
		bool $plain = false,
	): string|MetadataTypes\HardwareManufacturer|MetadataTypes\FirmwareManufacturer|MetadataTypes\DeviceModel|null
	{
		if ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER) {
			if ($this->content !== null && MetadataTypes\HardwareManufacturer::isValidValue($this->content)) {
				return MetadataTypes\HardwareManufacturer::get($this->content);
			}

			if ($this->content !== null && $plain) {
				return $this->content;
			}

			return MetadataTypes\HardwareManufacturer::get(
				MetadataTypes\HardwareManufacturer::MANUFACTURER_GENERIC,
			);
		}

		if ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL) {
			if ($this->content !== null && MetadataTypes\DeviceModel::isValidValue($this->content)) {
				return MetadataTypes\DeviceModel::get($this->content);
			}

			if ($this->content !== null && $plain) {
				return $this->content;
			}

			return MetadataTypes\DeviceModel::get(MetadataTypes\DeviceModel::MODEL_CUSTOM);
		}

		if ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS) {
			if (
				$this->content !== null
				&& (
					preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $this->content) !== 0
					|| preg_match('/^([0-9A-Fa-f]{12})$/', $this->content) !== 0
				)
			) {
				return implode(
					':',
					str_split(Utils\Strings::lower(str_replace([':', '-'], '', $this->content)), 2),
				);
			}

			return null;
		}

		if ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER) {
			if ($this->content !== null && MetadataTypes\FirmwareManufacturer::isValidValue($this->content)) {
				return MetadataTypes\FirmwareManufacturer::get($this->content);
			}

			if ($this->content !== null && $plain) {
				return $this->content;
			}

			return MetadataTypes\FirmwareManufacturer::get(
				MetadataTypes\FirmwareManufacturer::MANUFACTURER_GENERIC,
			);
		}

		return $this->content;
	}

	/**
	 * @throws Exceptions\InvalidArgument
	 */
	public function setContent(
		string|MetadataTypes\HardwareManufacturer|MetadataTypes\FirmwareManufacturer|MetadataTypes\DeviceModel|null $content,
	): void
	{
		if ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MANUFACTURER) {
			if ($content instanceof MetadataTypes\HardwareManufacturer) {
				$this->content = strval($content->getValue());
			} else {
				$this->content = $content !== null ? Utils\Strings::lower((string) $content) : null;
			}
		} elseif ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MODEL) {
			if ($content instanceof MetadataTypes\DeviceModel) {
				$this->content = strval($content->getValue());
			} else {
				$this->content = $content !== null ? Utils\Strings::lower((string) $content) : null;
			}
		} elseif ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_HARDWARE_MAC_ADDRESS) {
			if (
				$content !== null
				&& preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', (string) $content) === 0
				&& preg_match('/^([0-9A-Fa-f]{12})$/', (string) $content) === 0
			) {
				throw new Exceptions\InvalidArgument('Provided mac address is not in valid format.');
			}

			$this->content = $content !== null ? Utils\Strings::lower(str_replace([
				':',
				'-',
			], '', (string) $content)) : null;
		} elseif ($this->getIdentifier() === MetadataTypes\DeviceAttributeIdentifier::IDENTIFIER_FIRMWARE_MANUFACTURER) {
			if ($content instanceof MetadataTypes\FirmwareManufacturer) {
				$this->content = strval($content->getValue());
			} else {
				$this->content = $content !== null ? Utils\Strings::lower((string) $content) : null;
			}
		} elseif (is_string($content) || $content === null) {
			$this->content = $content;
		}
	}

	public function getDevice(): Entities\Devices\Device
	{
		return $this->device;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'id' => $this->getPlainId(),
			'identifier' => $this->getIdentifier(),
			'name' => $this->getName(),
			'content' => is_scalar(
				$this->getContent(),
			) || $this->getContent() === null ? $this->getContent() : (string) $this->getContent(),

			'device' => $this->getDevice()->getPlainId(),

			'owner' => $this->getDevice()->getOwnerId(),
		];
	}

	public function getSource(): MetadataTypes\ModuleSource
	{
		return MetadataTypes\ModuleSource::get(MetadataTypes\ModuleSource::SOURCE_MODULE_DEVICES);
	}

}
