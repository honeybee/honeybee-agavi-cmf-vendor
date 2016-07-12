<?php

namespace Honeybee\Tests\Fixture\BookSchema\Model\Author;

use Honeybee\Tests\Fixture\BookSchema\Model\AggregateRootType;
use Honeybee\Tests\Fixture\BookSchema\Model\Author\Embed\HighlightType;
use Trellis\Runtime\Attribute\Email\EmailAttribute;
use Trellis\Runtime\Attribute\EmbeddedEntityList\EmbeddedEntityListAttribute;
use Trellis\Runtime\Attribute\EntityReferenceList\EntityReferenceListAttribute;
use Trellis\Runtime\Attribute\ImageList\ImageListAttribute;
use Trellis\Runtime\Attribute\Text\TextAttribute;
use Trellis\Runtime\Attribute\Token\TokenAttribute;
use Workflux\StateMachine\StateMachineInterface;

class AuthorType extends AggregateRootType
{
    public function __construct(StateMachineInterface $state_machine)
    {
        $this->workflow_state_machine = $state_machine;

        parent::__construct(
            'Author',
            [
                new TextAttribute('firstname', $this, [ 'mandatory' => true, 'min_length' => 2 ]),
                new TextAttribute('lastname', $this, [ 'min_length' => 2 ]),
                new EmailAttribute('email', $this, [ 'mandatory' => true ]),
                new TextAttribute('blurb', $this, [ 'default_value' =>  'the grinch' ]),
                new TokenAttribute('token', $this),
                new ImageListAttribute('images', $this),
                new EmbeddedEntityListAttribute(
                    'products',
                    $this,
                    [
                        'max_count' => 2,
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Fixture\\BookSchema\\Model\\Author\\Embed\\HighlightType',
                        ]
                    ]
                ),
                new EntityReferenceListAttribute(
                    'books',
                    $this,
                    [
                        'inline_mode' => true,
                        'entity_types' => [
                            '\\Honeybee\\Tests\\Fixture\\BookSchema\\Model\\Author\\Reference\\BookType'
                        ]
                    ]
                )
            ]
        );
    }

    public static function getEntityImplementor()
    {
        return Author::CLASS;
    }
}
