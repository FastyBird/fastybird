<?php declare(strict_types = 1);

/**
 * Publisher.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Publishers
 * @since          1.0.0
 *
 * @date           19.12.20
 */

namespace FastyBird\Library\Exchange\Publisher\Async;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Types as MetadataTypes;
use React\Promise;

/**
 * Exchange asynchronous publisher interface
 *
 * @package        FastyBird:ExchangeLibrary!
 * @subpackage     Publishers
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
interface Publisher
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function publish(
		MetadataTypes\Sources\Source $source,
		string $routingKey,
		MetadataDocuments\Document|null $entity,
	): Promise\PromiseInterface;

}
