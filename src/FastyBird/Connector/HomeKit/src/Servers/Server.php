<?php declare(strict_types = 1);

/**
 * Server.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Servers
 * @since          1.0.0
 *
 * @date           17.09.22
 */

namespace FastyBird\Connector\HomeKit\Servers;

/**
 * HomeKit device server interface
 *
 * @package        FastyBird:HomeKitConnector!
 * @subpackage     Servers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Server
{

	/**
	 * Initialize server
	 */
	public function initialize(): void;

	/**
	 * Create server
	 */
	public function connect(): void;

	/**
	 * Destroy server
	 */
	public function disconnect(): void;

}
