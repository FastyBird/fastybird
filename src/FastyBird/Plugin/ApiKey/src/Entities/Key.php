<?php declare(strict_types = 1);

/**
 * Key.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:ApiKeyPlugin!
 * @subpackage     Entities
 * @since          0.1.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Plugin\ApiKey\Entities;

use Consistence\Doctrine\Enum\EnumAnnotation as Enum;
use Doctrine\ORM\Mapping as ORM;
use FastyBird\Plugin\ApiKey\Entities;
use FastyBird\Plugin\ApiKey\Types;
use IPub\DoctrineCrud;
use IPub\DoctrineCrud\Mapping\Annotation as IPubDoctrine;
use IPub\DoctrineTimestampable;
use Ramsey\Uuid;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="fb_api_key_plugin_keys",
 *     options={
 *       "collate"="utf8mb4_general_ci",
 *       "charset"="utf8mb4",
 *       "comment"="API Key plugin access keys"
 *     }
 * )
 */
class Key extends Entities\Entity implements DoctrineCrud\Entities\IEntity,
	DoctrineTimestampable\Entities\IEntityCreated, DoctrineTimestampable\Entities\IEntityUpdated
{

	use DoctrineTimestampable\Entities\TEntityCreated;
	use DoctrineTimestampable\Entities\TEntityUpdated;

	/**
	 * @ORM\Id
	 * @ORM\Column(type="uuid_binary", name="key_id")
	 * @ORM\CustomIdGenerator(class="Ramsey\Uuid\Doctrine\UuidGenerator")
	 */
	protected Uuid\UuidInterface $id;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="key_name", length=50, nullable=false)
	 */
	private string $name;

	/**
	 * @IPubDoctrine\Crud(is={"required", "writable"})
	 * @ORM\Column(type="string", name="key_key", length=150, nullable=false)
	 */
	private string $key;

	/**
	 * @Enum(class=Types\KeyStateType::class)
	 * @IPubDoctrine\Crud(is={"writable"})
	 * @ORM\Column(type="string_enum", name="key_state", length=10, nullable=false, options={"default": "active"})
	 */
	private Types\KeyState $state;

	public function __construct(
		string $name,
		string $key,
		Types\KeyState $state,
		Uuid\UuidInterface|null $id = null,
	)
	{
		$this->id = $id ?? Uuid\Uuid::uuid4();

		$this->state = $state;

		$this->name = $name;
		$this->key = $key;
	}

	public function setName(string $name): void
	{
		$this->name = $name;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setKey(string $key): void
	{
		$this->key = $key;
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function setState(Types\KeyState $state): void
	{
		$this->state = $state;
	}

	public function getState(): Types\KeyState
	{
		return $this->state;
	}

}
