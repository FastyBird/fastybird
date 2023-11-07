<?php declare(strict_types = 1);

/**
 * FindThirdPartyDevices.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queries
 * @since          1.0.0
 *
 * @date           29.07.23
 */

namespace FastyBird\Connector\NsPanel\Queries\Entities;

use FastyBird\Connector\NsPanel\Entities;

/**
 * Find third-party devices entities query
 *
 * @template T of Entities\Devices\ThirdPartyDevice
 * @extends  FindDevices<T>
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Queries
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class FindThirdPartyDevices extends FindDevices
{

}
