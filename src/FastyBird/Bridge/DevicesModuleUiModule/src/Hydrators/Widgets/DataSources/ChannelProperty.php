<?php declare(strict_types = 1);

/**
 * ChannelProperty.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           04.08.24
 */

namespace FastyBird\Bridge\DevicesModuleUiModule\Hydrators\Widgets\DataSources;

use Doctrine\Persistence;
use FastyBird\Bridge\DevicesModuleUiModule\Entities;
use FastyBird\Bridge\DevicesModuleUiModule\Schemas;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\JsonApi\Helpers;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Devices\Entities as DevicesEntities;
use FastyBird\Module\Devices\Models as DevicesModels;
use FastyBird\Module\Ui\Hydrators as UiHydrators;
use Fig\Http\Message\StatusCodeInterface;
use IPub\JsonAPIDocument;
use Nette\Localization;
use Ramsey\Uuid;
use function is_string;
use function strval;

/**
 * Channel property data source entity hydrator
 *
 * @extends UiHydrators\Widgets\DataSources\DataSource<Entities\Widgets\DataSources\ChannelProperty>
 *
 * @package        FastyBird:DevicesModuleUiModuleBridge!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
final class ChannelProperty extends UiHydrators\Widgets\DataSources\DataSource
{

	/** @var array<string> */
	protected array $relationships = [
		Schemas\Widgets\DataSources\ChannelProperty::RELATIONSHIPS_PROPERTY,
	];

	public function __construct(
		private readonly DevicesModels\Entities\Channels\Properties\PropertiesRepository $propertiesRepository,
		Persistence\ManagerRegistry $managerRegistry,
		Localization\Translator $translator,
		Helpers\CrudReader|null $crudReader = null,
	)
	{
		parent::__construct($managerRegistry, $translator, $crudReader);
	}

	public function getEntityName(): string
	{
		return Entities\Widgets\DataSources\ChannelProperty::class;
	}

	/**
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydratePropertyRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
		Entities\Widgets\DataSources\ChannelProperty|null $entity,
	): DevicesEntities\Channels\Properties\Property
	{
		if (
			$relationship->getData() instanceof JsonAPIDocument\Objects\IResourceIdentifierObject
			&& is_string($relationship->getData()->getId())
			&& Uuid\Uuid::isValid($relationship->getData()->getId())
		) {
			$property = $this->propertiesRepository->find(
				Uuid\Uuid::fromString($relationship->getData()->getId()),
			);

			if ($property !== null) {
				return $property;
			}
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval(
				$this->translator->translate('//devices-module-ui-module-bridge.base.messages.invalidRelation.heading'),
			),
			strval(
				$this->translator->translate('//devices-module-ui-module-bridge.base.messages.invalidRelation.message'),
			),
			[
				'pointer' => '/data/relationships/' . Schemas\Widgets\DataSources\ChannelProperty::RELATIONSHIPS_PROPERTY . '/data/id',
			],
		);
	}

}
