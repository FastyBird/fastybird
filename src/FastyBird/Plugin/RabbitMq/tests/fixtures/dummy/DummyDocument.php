<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Tests\Fixtures\Dummy;

use FastyBird\Library\Metadata\Documents as MetadataDocuments;
use FastyBird\Library\Metadata\Documents\Mapping as DOC;
use Orisai\ObjectMapper;

#[DOC\Document]
final class DummyDocument implements MetadataDocuments\Document
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue()]
		private readonly string $attribute,
		#[ObjectMapper\Rules\IntValue()]
		private readonly int $value,
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
