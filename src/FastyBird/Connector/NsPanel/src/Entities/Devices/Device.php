<?php declare(strict_types = 1);

/**
 * Device.php
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

namespace FastyBird\Connector\NsPanel\Entities\Devices;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Connector\NsPanel\Entities;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function assert;

#[ORM\MappedSuperclass]
abstract class Device extends DevicesEntities\Devices\Device
{

	public function getSource(): MetadataTypes\Sources\Connector
	{
		return MetadataTypes\Sources\Connector::get(MetadataTypes\Sources\Connector::NS_PANEL);
	}

	public function getConnector(): Entities\Connectors\Connector
	{
		assert($this->connector instanceof Entities\Connectors\Connector);

		return $this->connector;
	}

}
