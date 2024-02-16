<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RedisDb\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use Orisai\ObjectMapper;

#[DOC\Document]
final readonly class DummyDocument implements MetadataDocuments\Document
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue()]
		private string $attribute,
		#[ObjectMapper\Rules\IntValue()]
		private int $value,
	)
	{
	}

	public function getAttribute(): string
	{
		return $this->attribute;
	}

	public function getValue(): int
	{
		return $this->value;
	}

	public function toArray(): array
	{
		return [
			'attribute' => $this->getAttribute(),
			'value' => $this->getValue(),
		];
	}

}
