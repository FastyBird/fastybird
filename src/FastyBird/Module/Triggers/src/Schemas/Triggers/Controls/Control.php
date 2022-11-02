<?php declare(strict_types = 1);

/**
 * ControlSchema.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:TriggersModule!
 * @subpackage     Schemas
 * @since          0.4.0
 *
 * @date           01.10.21
 */

namespace FastyBird\Module\Triggers\Schemas\Triggers\Controls;

use FastyBird\JsonApi\Schemas as JsonApiSchemas;
use FastyBird\Library\Metadata\Types\ModuleSourceType;
use FastyBird\Module\Triggers;
use FastyBird\Module\Triggers\Entities;
use FastyBird\Module\Triggers\Router;
use FastyBird\Module\Triggers\Schemas;
use IPub\SlimRouter\Routing;
use Neomerx\JsonApi;

/**
 * Trigger control entity schema
 *
 * @package         FastyBird:TriggersModule!
 * @subpackage      Schemas
 *
 * @author          Adam Kadlec <adam.kadlec@fastybird.com>
 *
 * @phpstan-extends JsonApiSchemas\JsonApiSchema<Entities\Triggers\Controls\IControl>
 */
final class ControlSchema extends JsonApiSchemas\JsonApiSchema
{

	/**
	 * Define entity schema type string
	 */
	public const SCHEMA_TYPE = ModuleSourceType::SOURCE_MODULE_TRIGGERS . '/control/trigger';

	/**
	 * Define relationships names
	 */
	public const RELATIONSHIPS_TRIGGER = 'trigger';

	/** @var Routing\IRouter */
	private Routing\IRouter $router;

	public function __construct(
		Routing\IRouter $router
	) {
		$this->router = $router;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEntityClass(): string
	{
		return Entities\Triggers\Controls\Control::class;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return self::SCHEMA_TYPE;
	}

	/**
	 * @param Entities\Triggers\Controls\IControl $control
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, string|bool|null>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getAttributes($control, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return [
			'name' => $control->getName(),
		];
	}

	/**
	 * @param Entities\Triggers\Controls\IControl $control
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getSelfLink($control): JsonApi\Contracts\Schema\LinkInterface
	{
		return new JsonApi\Schema\Link(
			false,
			$this->router->urlFor(
				TriggersModule\Constants::ROUTE_NAME_TRIGGER_CONTROL,
				[
					Router\Routes::URL_TRIGGER_ID => $control->getTrigger()->getPlainId(),
					Router\Routes::URL_ITEM_ID    => $control->getPlainId(),
				]
			),
			false
		);
	}

	/**
	 * @param Entities\Triggers\Controls\IControl $control
	 * @param JsonApi\Contracts\Schema\ContextInterface $context
	 *
	 * @return iterable<string, mixed>
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationships($control, JsonApi\Contracts\Schema\ContextInterface $context): iterable
	{
		return [
			self::RELATIONSHIPS_TRIGGER => [
				self::RELATIONSHIP_DATA          => $control->getTrigger(),
				self::RELATIONSHIP_LINKS_SELF    => false,
				self::RELATIONSHIP_LINKS_RELATED => true,
			],
		];
	}

	/**
	 * @param Entities\Triggers\Controls\IControl $control
	 * @param string $name
	 *
	 * @return JsonApi\Contracts\Schema\LinkInterface
	 *
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingParameterTypeHint
	 */
	public function getRelationshipRelatedLink($control, string $name): JsonApi\Contracts\Schema\LinkInterface
	{
		if ($name === self::RELATIONSHIPS_TRIGGER) {
			return new JsonApi\Schema\Link(
				false,
				$this->router->urlFor(
					TriggersModule\Constants::ROUTE_NAME_TRIGGER,
					[
						Router\Routes::URL_ITEM_ID => $control->getTrigger()->getPlainId(),
					]
				),
				false
			);
		}

		return parent::getRelationshipRelatedLink($control, $name);
	}

}
