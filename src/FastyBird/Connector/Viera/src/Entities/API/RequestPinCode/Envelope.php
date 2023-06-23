<?php declare(strict_types = 1);

/**
 * Envelope.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           22.06.23
 */

namespace FastyBird\Connector\Viera\Entities\API\RequestPinCode;

use FastyBird\Connector\Viera\Entities;

/**
 * Request pin code envelope entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Envelope implements Entities\API\Entity
{

	public Body|null $Body = null;

	public function getBody(): Body|null
	{
		return $this->Body;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'body' => $this->getBody()?->toArray(),
		];
	}

}
