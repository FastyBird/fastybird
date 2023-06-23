<?php declare(strict_types = 1);

/**
 * DisplayPinCodeResponse.php
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
 * Request pin code entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DisplayPinCodeResponse implements Entities\API\Entity
{

	public string|null $X_ChallengeKey = null;

	/**
	 * @return string|null
	 */
	public function getXChallengeKey(): ?string
	{
		return $this->X_ChallengeKey;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'challenge_key' => $this->getXChallengeKey(),
		];
	}

}
