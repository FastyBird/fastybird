<?php declare(strict_types = 1);

/**
 * ICouchDbConnection.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Connections
 * @since          0.1.0
 *
 * @date           03.03.20
 */

namespace FastyBird\Plugin\CouchDb\Connections;

use PHPOnCouch;

/**
 * Couch DB connection configuration interface
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     Connections
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface ICouchDbConnection
{

	public function getHost(): string;

	public function getPort(): int;

	public function getUsername(): string|null;

	public function getPassword(): string|null;

	public function getDatabase(): string;

	public function getClient(): PHPOnCouch\CouchClient;

}
