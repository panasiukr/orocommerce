<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model;

use Oro\Bundle\ActionBundle\Model\ActionContext;
use Oro\Bundle\ActionBundle\Model\AttributeAssembler;

use Oro\Bundle\WorkflowBundle\Model\Attribute;
use Oro\Bundle\WorkflowBundle\Model\AttributeGuesser;

class AttributeAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ActionContext */
    protected $actionContext;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AttributeGuesser */
    protected $attributeGuesser;

    /** @var AttributeAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->actionContext = $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionContext')
            ->disableOriginalConstructor()
            ->getMock();
        $this->actionContext->expects($this->any())
            ->method('getEntity')
            ->willReturn(new \stdClass());

        $this->attributeGuesser = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\AttributeGuesser')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeGuesser->expects($this->any())
            ->method('fixPropertyPath')
            ->willReturnArgument(0);

        $this->assembler = new AttributeAssembler($this->attributeGuesser);
    }

    /**
     * @dataProvider invalidOptionsDataProvider
     *
     * @param array $configuration
     * @param string $message
     */
    public function testAssembleRequiredOptionException(array $configuration, $message)
    {
        $this->setExpectedException('Oro\Bundle\ActionBundle\Exception\AssemblerException', $message);

        $this->assembler->assemble($this->actionContext, $configuration);
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function invalidOptionsDataProvider()
    {
        return [
            'no_options' => [
                [
                    'attribute_name' => [
                        'property_path' => null
                    ]
                ],
                'Option "label" is required'
            ],
            'no_type' => [
                [
                    'attribute_name' => [
                        'label' => 'test',
                        'property_path' => null
                    ]
                ],
                'Option "type" is required'
            ],
            'no_label' => [
                [
                    'attribute_name' => [
                        'type' => 'test',
                        'property_path' => null
                    ]
                ],
                'Option "label" is required'
            ],
            'invalid_type' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'text',
                        'property_path' => null
                    ]
                ],
                'Invalid attribute type "text", allowed types are "bool", "boolean", "int", "integer", ' .
                    '"float", "string", "array", "object", "entity"'
            ],
            'invalid_type_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'string',
                        'options' => [
                            'class' => 'stdClass'
                        ],
                        'property_path' => null
                    ]
                ],
                'Option "class" cannot be used in attribute "attribute_name"'
            ],
            'missing_object_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'property_path' => null
                    ]
                ],
                'Option "class" is required in attribute "attribute_name"'
            ],
            'missing_entity_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'entity',
                        'property_path' => null
                    ]
                ],
                'Option "class" is required in attribute "attribute_name"'
            ],
            'invalid_class' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => [
                            'class' => 'InvalidClass'
                        ],
                        'property_path' => null
                    ]
                ],
                'Class "InvalidClass" referenced by "class" option in attribute "attribute_name" not found'
            ],
            'not_allowed_entity_acl' => [
                [
                    'attribute_name' => [
                        'label' => 'Label',
                        'type' => 'object',
                        'options' => [
                            'class' => 'stdClass'
                        ],
                        'entity_acl' => [
                            'update' => false
                        ],
                    ]
                ],
                'Attribute "Label" with type "object" can\'t have entity ACL'
            ],
        ];
    }

    /**
     * @dataProvider configurationDataProvider
     *
     * @param array $configuration
     * @param Attribute $expectedAttribute
     * @param array $guessedParameters
     */
    public function testAssemble(array $configuration, $expectedAttribute, array $guessedParameters = [])
    {
        $attributeConfiguration = current($configuration);
        if ($guessedParameters && array_key_exists('property_path', $attributeConfiguration)) {
            $this->attributeGuesser->expects($this->any())
                ->method('guessAttributeParameters')
                ->with('stdClass', $attributeConfiguration['property_path'])
                ->willReturn($guessedParameters);
        }

        $attributes = $this->assembler->assemble($this->actionContext, $configuration);

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $attributes);
        $this->assertCount(array_key_exists('entity', $configuration) ? 1 : 2, $attributes);
        $this->assertTrue($attributes->containsKey($expectedAttribute->getName()));
        $this->assertEquals($expectedAttribute, $attributes->get($expectedAttribute->getName()));
    }

    /**
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configurationDataProvider()
    {
        return [
            'string' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'string'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'string')
            ],
            'bool' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'bool'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'bool')
            ],
            'boolean' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'boolean'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'boolean')
            ],
            'int' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'int'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'int')
            ],
            'integer' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'integer'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'integer')
            ],
            'float' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'float'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'float')
            ],
            'array' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'array'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'array')
            ],
            'object' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'object',
                        'options' => ['class' => 'stdClass'],
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'object', ['class' => 'stdClass'])
            ],
            'entity_minimal' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'entity', ['class' => 'stdClass'])
            ],
            'with_related_entity' => [
                [
                    'entity' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],

                    ]
                ],
                $this->getAttribute('entity', 'label', 'entity', ['class' => 'stdClass'])
            ],
            'with_entity_acl' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'entity_acl' => ['update' => false],
                    ]
                ],
                $this->getAttribute(
                    'attribute_one',
                    'label',
                    'entity',
                    ['class' => 'stdClass'],
                    null,
                    ['update' => false]
                )
            ],
            'entity_minimal_guessed_parameters' => [
                [
                    'attribute_one' => [
                        'property_path' => 'entity.field'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'entity', ['class' => 'stdClass'], 'entity.field'),
                'guessedParameters' => [
                    'label' => 'label',
                    'type' => 'entity',
                    'options' => ['class' => 'stdClass'],
                ],
            ],
            'entity_full_guessed_parameters' => [
                [
                    'attribute_one' => [
                        'label' => 'label',
                        'type' => 'entity',
                        'options' => ['class' => 'stdClass'],
                        'property_path' => 'entity.field'
                    ]
                ],
                $this->getAttribute('attribute_one', 'label', 'entity', ['class' => 'stdClass'], 'entity.field'),
                'guessedParameters' => [
                    'label' => 'guessed label',
                    'type' => 'object',
                    'options' => ['class' => 'GuessedClass'],
                ],
            ],
        ];
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     * @param string $propertyPath
     * @param array $entityAcl
     * @return Attribute
     */
    protected function getAttribute(
        $name,
        $label,
        $type,
        array $options = [],
        $propertyPath = null,
        array $entityAcl = []
    ) {
        $attribute = new Attribute();
        $attribute->setName($name);
        $attribute->setLabel($label);
        $attribute->setType($type);
        $attribute->setOptions($options);
        $attribute->setPropertyPath($propertyPath);
        $attribute->setEntityAcl($entityAcl);

        return $attribute;
    }
}
