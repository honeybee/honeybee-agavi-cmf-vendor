<?php

namespace Honeygavi\Tests\Fixture\BookSchema\Model\Author\Reference;

use Honeybee\Model\Aggregate\ReferencedEntityType;
use Trellis\Common\Options;
use Trellis\Runtime\Attribute\AttributeInterface;
use Trellis\Runtime\Attribute\Text\TextAttribute;
use Trellis\Runtime\EntityTypeInterface;

class BookType extends ReferencedEntityType
{
    public function __construct(EntityTypeInterface $parent = null, AttributeInterface $parent_attribute = null)
    {
        parent::__construct(
            'Book',
            [
                new TextAttribute('title', $this, [ 'mandatory' => true ], $parent_attribute)
            ],
            new Options(
                [
                    'referenced_type' => '\\Honeygavi\\Tests\\Model\\Aggregate\\Fixture\\Book\\BookType',
                    'identifying_attribute' => 'identifier',
                ]
            ),
            $parent,
            $parent_attribute
        );
    }

    public static function getEntityImplementor()
    {
        return Book::CLASS;
    }
}
