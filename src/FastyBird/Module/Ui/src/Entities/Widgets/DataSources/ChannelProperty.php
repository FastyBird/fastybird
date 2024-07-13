<?php declare(strict_types = 1);

/**
 * ChannelPropertyDataSource.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:UIModule!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           25.05.20
 */

namespace FastyBird\Module\Ui\Entities\Widgets\DataSources;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Library\Application\Entities\Mapping as ApplicationMapping;
use FastyBird\Module\Ui\Entities;
use IPub\DoctrineCrud\Mapping\Attribute as IPubDoctrine;
use Ramsey\Uuid;
use function array_merge;

#[ORM\Entity]
#[ORM\Table(
	name: 'fb_ui_module_widgets_data_sources_channels_properties',
	options: [
		'collate' => 'utf8mb4_general_ci',
		'charset' => 'utf8mb4',
		'comment' => 'Widget data source connection to channel',
	],
)]
#[ApplicationMapping\DiscriminatorEntry(name: self::TYPE)]
class ChannelProperty extends DataSource
{

	public const TYPE = 'channel-property';

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'data_source_channel', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: false)]
	private Uuid\UuidInterface $channel;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'data_source_property', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: false)]
	private Uuid\UuidInterface $property;

	public function __construct(
		Uuid\UuidInterface $channel,
		Uuid\UuidInterface $property,
		Entities\Widgets\Widget $widget,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($widget, $id);

		$this->channel = $channel;
		$this->property = $property;
	}

	public static function getType(): string
	{
		return self::TYPE;
	}

	public function getChannel(): Uuid\UuidInterface
	{
		return $this->channel;
	}

	public function setChannel(Uuid\UuidInterface $channel): void
	{
		$this->channel = $channel;
	}

	public function getProperty(): Uuid\UuidInterface
	{
		return $this->property;
	}

	public function setProperty(Uuid\UuidInterface $property): void
	{
		$this->property = $property;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'channel' => $this->getChannel()->toString(),
			'property' => $this->getProperty()->toString(),
		]);
	}

}
