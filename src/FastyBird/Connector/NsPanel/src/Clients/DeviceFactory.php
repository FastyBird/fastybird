<?php declare(strict_types = 1);

/**
 * DeviceFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 * @since          1.0.0
 *
 * @date           16.07.23
 */

namespace FastyBird\Connector\NsPanel\Clients;

use FastyBird\Connector\NsPanel\Types;
use FastyBird\Library\Metadata\Documents as MetadataDocuments;

/**
 * Connector third-party device client factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Clients
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface DeviceFactory extends ClientFactory
{

	public const MODE = Types\ClientMode::DEVICE;

	public function create(MetadataDocuments\DevicesModule\Connector $connector): Device;

}
