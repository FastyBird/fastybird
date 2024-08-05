<?php declare(strict_types = 1);

/**
 * WidgetHydrator.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @since          1.0.0
 *
 * @date           26.05.20
 */

namespace FastyBird\Module\Ui\Hydrators\Widgets;

use Contributte\Translation;
use Doctrine\Persistence;
use FastyBird\JsonApi\Exceptions as JsonApiExceptions;
use FastyBird\JsonApi\Hydrators as JsonApiHydrators;
use FastyBird\JsonApi\JsonApi as JsonApiJsonApi;
use FastyBird\Library\Application\Exceptions as ApplicationExceptions;
use FastyBird\Module\Ui\Entities;
use FastyBird\Module\Ui\Hydrators;
use FastyBird\Module\Ui\Models;
use FastyBird\Module\Ui\Queries;
use FastyBird\Module\Ui\Schemas;
use Fig\Http\Message\StatusCodeInterface;
use IPub\DoctrineCrud\Entities as DoctrineCrudEntities;
use IPub\JsonAPIDocument;
use Nette\DI;
use Ramsey\Uuid;
use function assert;
use function is_scalar;
use function strval;

/**
 * Widget entity hydrator
 *
 * @template  T of Entities\Widgets\Widget
 * @extends   JsonApiHydrators\Hydrator<T>
 *
 * @package        FastyBird:UIModule!
 * @subpackage     Hydrators
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
abstract class Widget extends JsonApiHydrators\Hydrator
{

	/** @var array<int|string, string> */
	protected array $attributes = [
		0 => 'identifier',
		1 => 'name',

		'minimum_value' => 'minimumValue',
		'maximum_value' => 'maximumValue',
		'step_value' => 'stepValue',
		'precision' => 'precision',
		'enable_min_max' => 'enableMinMax',
		'icon' => 'icon',
	];

	/** @var array<string> */
	protected array $relationships = [
		0 => Schemas\Widgets\Widget::RELATIONSHIPS_DISPLAY,
		1 => Schemas\Widgets\Widget::RELATIONSHIPS_TABS,
		2 => Schemas\Widgets\Widget::RELATIONSHIPS_GROUPS,
		Schemas\Widgets\Widget::RELATIONSHIPS_DATA_SOURCES => 'dataSources',
	];

	/** @var JsonApiJsonApi\SchemaContainer<DoctrineCrudEntities\IEntity>|null */
	private JsonApiJsonApi\SchemaContainer|null $jsonApiSchemaContainer = null;

	/** @var array<Hydrators\Widgets\DataSources\DataSource<Entities\Widgets\DataSources\DataSource>>|null  */
	private array|null $dataSourcesHydrators = null;

	public function __construct(
		private readonly Models\Entities\Dashboards\Tabs\Repository $tabsRepository,
		private readonly Models\Entities\Groups\Repository $groupsRepository,
		private readonly DI\Container $container,
		Persistence\ManagerRegistry $managerRegistry,
		Translation\Translator $translator,
	)
	{
		parent::__construct($managerRegistry, $translator);
	}

	protected function hydrateNameAttribute(JsonAPIDocument\Objects\IStandardObject $attributes): string|null
	{
		if (
			!is_scalar($attributes->get('name'))
			|| (string) $attributes->get('name') === ''
		) {
			return null;
		}

		return (string) $attributes->get('name');
	}

	/**
	 * @return array<mixed>|null
	 *
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws JsonApiExceptions\InvalidState
	 */
	protected function hydrateDisplayRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
	): array|null
	{
		if (!$relationship->isHasOne()) {
			return null;
		}

		if ($included !== null) {
			foreach ($included->getAll() as $item) {
				if ($item->getId() === $relationship->getIdentifier()->getId()) {
					return $this->buildDisplay($item->getType(), $item->getAttributes(), $item->getId());
				}
			}
		}

		return null;
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws JsonApiExceptions\JsonApiError
	 * @throws JsonApiExceptions\InvalidState
	 */
	private function buildDisplay(
		string $type,
		JsonAPIDocument\Objects\IStandardObject $attributes,
		string|null $identifier = null,
	): array
	{
		switch ($type) {
			case Schemas\Widgets\Display\AnalogValue::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\AnalogValue::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\AnalogValue::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\AnalogValue::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
			case Schemas\Widgets\Display\Button::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\Button::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\Button::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\Button::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
			case Schemas\Widgets\Display\ChartGraph::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\ChartGraph::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\ChartGraph::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\ChartGraph::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
			case Schemas\Widgets\Display\DigitalValue::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\DigitalValue::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\DigitalValue::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\DigitalValue::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
			case Schemas\Widgets\Display\Gauge::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\Gauge::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\Gauge::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\Gauge::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
			case Schemas\Widgets\Display\GroupedButton::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\GroupedButton::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\GroupedButton::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\GroupedButton::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
			case Schemas\Widgets\Display\Slider::SCHEMA_TYPE:
				$entityMapping = $this->mapEntity(Entities\Widgets\Display\Slider::class);

				$display = $this->hydrateAttributes(
					Entities\Widgets\Display\Slider::class,
					$attributes,
					$entityMapping,
					null,
					null,
				);

				$display['entity'] = Entities\Widgets\Display\Slider::class;
				$display[self::IDENTIFIER_KEY] = $identifier !== null && $identifier !== ''
					? Uuid\Uuid::fromString($identifier)
					: $identifier;

				return $display;
		}

		throw new JsonApiExceptions\JsonApiError(
			StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
			strval($this->translator->translate('//ui-module.base.messages.missingRelation.heading')),
			strval($this->translator->translate('//ui-module.base.messages.missingRelation.message')),
			[
				'pointer' => '/data/relationships/display/data',
			],
		);
	}

	/**
	 * @return array<mixed>
	 *
	 * @throws DI\MissingServiceException
	 * @throws JsonApiExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateDataSourcesRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
	): array
	{
		if ($included === null) {
			throw new JsonApiExceptions\JsonApiError(
				StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
				strval($this->translator->translate('//ui-module.base.messages.missingRelation.heading')),
				strval($this->translator->translate('//ui-module.base.messages.missingRelation.message')),
				[
					'pointer' => '/data/relationships/data-sources/data/id',
				],
			);
		}

		$dataSources = [];

		$dataSourcesHydrators = $this->getDataSourceHydrators();

		foreach ($relationship->getIdentifiers() as $dataSourceRelationIdentifier) {
			foreach ($included->getAll() as $item) {
				if ($item->getId() === $dataSourceRelationIdentifier->getId()) {
					foreach ($dataSourcesHydrators as $dataSourceHydrator) {
						$dataSourcesSchema = $this->getSchemaContainer()->getSchemaByClassName(
							$dataSourceHydrator->getEntityName(),
						);

						if ($dataSourcesSchema->getType() === $item->getType()) {
							$entityMapping = $this->mapEntity($dataSourceHydrator->getEntityName());

							$dataSource = $this->hydrateAttributes(
								$dataSourceHydrator->getEntityName(),
								$item->getAttributes(),
								$entityMapping,
								null,
								null,
							);

							if ($item->getId() !== null) {
								$dataSource[self::IDENTIFIER_KEY] = Uuid\Uuid::fromString($item->getId());
							}

							$dataSources[] = $dataSource;
						}
					}
				}
			}
		}

		return $dataSources;
	}

	/**
	 * @return array<mixed>|null
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateTabsRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
	): array|null
	{
		if (!$relationship->isHasMany()) {
			return null;
		}

		$tabs = [];

		foreach ($relationship->getIdentifiers() as $tabRelationIdentifier) {
			try {
				if ($tabRelationIdentifier->getId() !== null && $tabRelationIdentifier->getId() !== '') {
					$findQuery = new Queries\Entities\FindTabs();
					$findQuery->byId(Uuid\Uuid::fromString($tabRelationIdentifier->getId()));

					$tab = $this->tabsRepository->findOneBy($findQuery);

					if ($tab !== null) {
						$tabs[] = $tab;
					}
				}
			} catch (Uuid\Exception\InvalidUuidStringException) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.invalidIdentifier.heading')),
					strval($this->translator->translate('//ui-module.base.messages.invalidIdentifier.message')),
					[
						'pointer' => '/data/relationships/tabs/data/id',
					],
				);
			}
		}

		return $tabs;
	}

	/**
	 * @return array<mixed>|null
	 *
	 * @throws ApplicationExceptions\InvalidState
	 * @throws JsonApiExceptions\JsonApiError
	 */
	protected function hydrateGroupsRelationship(
		JsonAPIDocument\Objects\IRelationshipObject $relationship,
		JsonAPIDocument\Objects\IResourceObjectCollection|null $included,
	): array|null
	{
		if (!$relationship->isHasMany()) {
			return null;
		}

		$groups = [];

		foreach ($relationship->getIdentifiers() as $groupRelationIdentifier) {
			try {
				if ($groupRelationIdentifier->getId() !== null && $groupRelationIdentifier->getId() !== '') {
					$findQuery = new Queries\Entities\FindGroups();
					$findQuery->byId(Uuid\Uuid::fromString($groupRelationIdentifier->getId()));

					$group = $this->groupsRepository->findOneBy($findQuery);

					if ($group !== null) {
						$groups[] = $group;
					}
				}
			} catch (Uuid\Exception\InvalidUuidStringException) {
				throw new JsonApiExceptions\JsonApiError(
					StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY,
					strval($this->translator->translate('//ui-module.base.messages.invalidIdentifier.heading')),
					strval($this->translator->translate('//ui-module.base.messages.invalidIdentifier.message')),
					[
						'pointer' => '/data/relationships/groups/data/id',
					],
				);
			}
		}

		return $groups;
	}

	/**
	 * @return JsonApiJsonApi\SchemaContainer<DoctrineCrudEntities\IEntity>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getSchemaContainer(): JsonApiJsonApi\SchemaContainer
	{
		if ($this->jsonApiSchemaContainer !== null) {
			return $this->jsonApiSchemaContainer;
		}

		$this->jsonApiSchemaContainer = $this->container->getByType(JsonApiJsonApi\SchemaContainer::class);

		return $this->jsonApiSchemaContainer;
	}

	/**
	 * @return array<Hydrators\Widgets\DataSources\DataSource<Entities\Widgets\DataSources\DataSource>>
	 *
	 * @throws DI\MissingServiceException
	 */
	private function getDataSourceHydrators(): array
	{
		if ($this->dataSourcesHydrators !== null) {
			return $this->dataSourcesHydrators;
		}

		$this->dataSourcesHydrators = [];

		$serviceNames = $this->container->findByType(Hydrators\Widgets\DataSources\DataSource::class);

		foreach ($serviceNames as $serviceName) {
			$service = $this->container->getByName($serviceName);
			assert($service instanceof Hydrators\Widgets\DataSources\DataSource);

			$this->dataSourcesHydrators[] = $service;
		}

		return $this->dataSourcesHydrators;
	}

}
