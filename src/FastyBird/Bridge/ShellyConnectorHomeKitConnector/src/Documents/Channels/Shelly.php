<?php declare(strict_types = 1);

/**
 * Shelly.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ShellyConnectorHomeKitConnectorBridge!
 * @subpackage     Documents
 * @since          1.0.0
 *
 * @date           18.08.24
 */

namespace FastyBird\Bridge\ShellyConnectorHomeKitConnector\Documents\Channels;

use FastyBird\Connector\HomeKit\Documents as HomeKitDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;

#[DOC\MappedSuperclass()]
abstract class Shelly extends HomeKitDocuments\Channels\Channel
{

}
