<?php declare(strict_types = 1);

/**
 * ErrorEventPayload.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           31.07.23
 */

namespace FastyBird\Connector\NsPanel\Entities\API\Response;

use FastyBird\Connector\NsPanel\Entities;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel event error payload response definition
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Entities
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ErrorEventPayload implements Entities\API\Entity
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $description,
	)
	{
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'type' => $this->getType(),
			'description' => $this->getDescription(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->type = $this->getType();
		$json->description = $this->getDescription();

		return $json;
	}

}
