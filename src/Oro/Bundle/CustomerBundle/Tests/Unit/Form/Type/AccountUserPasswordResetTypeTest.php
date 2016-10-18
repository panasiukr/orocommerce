<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\CustomerBundle\Form\Type\AccountUserPasswordResetType;

class AccountUserPasswordResetTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'Oro\Bundle\CustomerBundle\Entity\AccountUser';

    /** @var AccountUserPasswordResetType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new AccountUserPasswordResetType();
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    protected function tearDown()
    {
        unset($this->formType);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitProvider
     *
     * @param AccountUser $defaultData
     * @param array $submittedData
     * @param AccountUser $expectedData
     */
    public function testSubmit($defaultData, array $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        $entity = new AccountUser();
        $expectedEntity = new AccountUser();
        $expectedEntity->setSalt($entity->getSalt());
        $expectedEntity->setPlainPassword('new password1Q');

        return [
            'reset password' => [
                'defaultData' => $entity,
                'submittedData' => [
                    'plainPassword' =>  [
                        'first' => 'new password1Q',
                        'second' => 'new password1Q'
                    ]
                ],
                'expectedData' => $expectedEntity
            ]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals(AccountUserPasswordResetType::NAME, $this->formType->getName());
    }
}
