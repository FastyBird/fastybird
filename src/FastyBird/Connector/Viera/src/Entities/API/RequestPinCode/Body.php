<?php declare(strict_types = 1);

/**
 * Device.php
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
 * Request pin code body entity
 *
 * @package        FastyBird:VieraConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Body implements Entities\API\Entity
{

	public DisplayPinCodeResponse|null $X_DisplayPinCodeResponse = null;

	public function getXDisplayPinCodeResponse(): DisplayPinCodeResponse|null
	{
		return $this->X_DisplayPinCodeResponse;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'display_pin_code_response' => $this->getXDisplayPinCodeResponse()->toArray(),
		];
	}

}
