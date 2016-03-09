<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension\Stub\AccountGroupTypeStub;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListsSettingsType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\PriceListCollectionTypeExtensionsProvider;
use OroB2B\Bundle\WebsiteBundle\Form\Type\WebsiteScopedDataType;
use OroB2B\Bundle\PricingBundle\EventListener\AbstractPriceListCollectionAwareListener;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListSelectWithPriorityType;
use OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type\Stub\PriceListSelectTypeStub;
use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\AccountBundle\Form\Type\AccountGroupType;
use OroB2B\Bundle\PricingBundle\Entity\PriceListToAccountGroup;
use OroB2B\Bundle\PricingBundle\EventListener\AccountGroupListener;
use OroB2B\Bundle\PricingBundle\Form\Extension\AccountGroupFormExtension;

class AccountGroupFormExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var AccountGroupListener $listener */
        $listener = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\EventListener\AccountGroupListener')
            ->disableOriginalConstructor()
            ->getMock();

        $provider = new PriceListCollectionTypeExtensionsProvider();
        $websiteScopedDataType = $this->getWebsiteScopedDataType();

        $extensions = [
            new PreloadedExtension(
                [
                    PriceListsSettingsType::NAME => new PriceListsSettingsType(),
                    WebsiteScopedDataType::NAME => $websiteScopedDataType,
                    AccountGroupType::NAME => new AccountGroupTypeStub()
                ],
                [
                    AccountGroupType::NAME => [new AccountGroupFormExtension($listener)]
                ]
            )
        ];

        return array_merge($provider->getExtensions(), $extensions);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submitted
     * @param array $expected
     */
    public function testSubmit(array $submitted, array $expected)
    {
        $form = $this->factory->create(AccountGroupType::NAME, [], []);
        $form->submit([AbstractPriceListCollectionAwareListener::PRICE_LISTS_BY_WEBSITES => $submitted]);
        $data = $form->get(AccountGroupListener::PRICE_LISTS_BY_WEBSITES)->getData();
        $this->assertTrue($form->isValid());
        $this->assertEquals($expected, $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submitted' => [
                    1 => [
                        PriceListsSettingsType::FALLBACK_FIELD => '0',
                        PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD =>
                            [
                                0 => [
                                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                                    => (string)PriceListSelectTypeStub::PRICE_LIST_1,
                                    PriceListSelectWithPriorityType::PRIORITY_FIELD => '200',
                                    PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => true,
                                ],
                                1 => [
                                    PriceListSelectWithPriorityType::PRICE_LIST_FIELD
                                    => (string)PriceListSelectTypeStub::PRICE_LIST_2,
                                    PriceListSelectWithPriorityType::PRIORITY_FIELD => '100',
                                    PriceListSelectWithPriorityType::MERGE_ALLOWED_FIELD => false,
                                ]
                            ],
                    ],
                ],
                'expected' => [
                    1 => [
                        PriceListsSettingsType::FALLBACK_FIELD => 0,
                        PriceListsSettingsType::PRICE_LIST_COLLECTION_FIELD =>
                            [
                                0 => (new PriceListToAccountGroup())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_1))
                                    ->setPriority(200)
                                    ->setMergeAllowed(true),
                                1 => (new PriceListToAccountGroup())
                                    ->setPriceList($this->getPriceList(PriceListSelectTypeStub::PRICE_LIST_2))
                                    ->setPriority(100)
                                    ->setMergeAllowed(false)
                            ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @param int $id
     * @return PriceList
     */
    protected function getPriceList($id)
    {
        return $this->getEntity('OroB2B\Bundle\PricingBundle\Entity\PriceList', [
            'id' => $id
        ]);
    }

    /**
     * @return WebsiteScopedDataType
     */
    protected function getWebsiteScopedDataType()
    {
        $website = $this->getEntity('OroB2B\Bundle\WebsiteBundle\Entity\Website', ['id' => 1, 'name' => 'US']);

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getReference')
            ->with('OroB2B\Bundle\WebsiteBundle\Entity\Website', 1)
            ->willReturn($website);

        $repository = $this->getMockBuilder('OroB2B\Bundle\WebsiteBundle\Entity\Repository\WebsiteRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $repository->expects($this->any())
            ->method('getAllWebsites')
            ->willReturn([$website]);

        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('\Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getRepository')
            ->with('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->willReturn($repository);

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroB2B\Bundle\WebsiteBundle\Entity\Website')
            ->willReturn($em);

        return new WebsiteScopedDataType($registry);
    }
}
