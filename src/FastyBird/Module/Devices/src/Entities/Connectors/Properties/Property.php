<?php declare(strict_types = 1);

/**
 * Property.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           08.02.22
 */

namespace FastyBird\Module\Devices\Entities\Connectors\Properties;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Exceptions as MetadataExceptions;
use FastyBird\Module\Devices\Entities;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use Nette\Utils;
use Ramsey\Uuid;
use function array_merge;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_devices_module_connectors_properties",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="Connectors properties"
 *     },
 *     uniqueConstraints={
 *       @ORM\UniqueConstraint(name="property_identifier_unique", columns={"property_identifier", "connector_id"})
 *     },
 *     indexes={
 *       @ORM\Index(name="property_identifier_idx", columns={"property_identifier"}),
 *       @ORM\Index(name="property_settable_idx", columns={"property_settable"}),
 *       @ORM\Index(name="property_queryable_idx", columns={"property_queryable"})
 *     }
 * )
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="property_type", type="string", length=40)
 * @ORM\DiscriminatorMap({
 *    "variable" = "FastyBird\Module\Devices\Entities\Connectors\Properties\Variable",
 *    "dynamic"  = "FastyBird\Module\Devices\Entities\Connectors\Properties\Dynamic"
 * })
 * @ORM\MappedSuperclass
 */
abstract class Property extends Entities\Property
{

	/**
	 * @IPubDoctrine\Crud(is="required")
	 * @ORM\ManyToOne(targetEntity="FastyBird\Module\Devices\Entities\Connectors\Connector", inversedBy="properties")
	 * @ORM\JoinColumn(name="connector_id", referencedColumnName="connector_id", onDelete="CASCADE", nullable=false)
	 */
	protected Entities\Connectors\Connector $connector;

	public function __construct(
		Entities\Connectors\Connector $connector,
		string $identifier,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($identifier, $id);

		$this->connector = $connector;

		$connector->addProperty($this);
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		return $this->connector;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'connector' => $this->getConnector()->getId()->toString(),

			'owner' => $this->getConnector()->getOwnerId(),
		]);
	}

	/**
	 * @throws MetadataExceptions\InvalidArgument
	 * @throws MetadataExceptions\InvalidState
	 * @throws Utils\JsonException
	 */
	public function __toString(): string
	{
		return Utils\Json::encode($this->toArray());
	}

}
