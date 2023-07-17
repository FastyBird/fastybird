<?php declare(strict_types = 1);

/**
 * NsPanelDevice.php
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

namespace FastyBird\Connector\NsPanel\Entities;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use function assert;

/**
 * @ORM\MappedSuperclass
 */
abstract class NsPanelDevice extends DevicesEntities\Devices\Device
{

	public function getSource(): MetadataTypes\ConnectorSource
	{
		return MetadataTypes\ConnectorSource::get(MetadataTypes\ConnectorSource::SOURCE_CONNECTOR_NS_PANEL);
	}

	public function getConnector(): NsPanelConnector
	{
		assert($this->connector instanceof NsPanelConnector);

		return $this->connector;
	}

}
