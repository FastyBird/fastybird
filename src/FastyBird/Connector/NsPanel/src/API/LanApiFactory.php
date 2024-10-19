<?php declare(strict_types = 1);

/**
 * LanApiFactory.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\API;

use Ramsey\Uuid;

/**
 * NS Panel LAN API interface factory
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     API
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface LanApiFactory
{

	public function create(Uuid\UuidInterface $id): LanApi;

}
