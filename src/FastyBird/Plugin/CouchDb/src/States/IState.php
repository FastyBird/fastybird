<?php declare(strict_types = 1);

/**
 * IState.php
 *
 * @license        More in license.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     States
 * @since          0.1.0
 *
 * @date           04.03.20
 */

namespace FastyBird\Plugin\CouchDb\States;

use PHPOnCouch;
use Ramsey\Uuid;

/**
 * Base state interface
 *
 * @package        FastyBird:CouchDbStoragePlugin!
 * @subpackage     States
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface IState
{

	public const CREATE_FIELDS = [
		'id',
	];

	public const UPDATE_FIELDS = [];

	public function getId(): Uuid\UuidInterface;

	public function getDocument(): PHPOnCouch\CouchDocument;

	/**
	 * @return array<mixed>
	 */
	public function toArray(): array;

}
