<?php

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PromotionBundle\Discount\DiscountContext;
use Oro\Bundle\PromotionBundle\Executor\PromotionExecutor;
use Oro\Bundle\PromotionBundle\Provider\SubtotalProvider;
use Symfony\Component\Translation\TranslatorInterface;

class SubtotalProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $currencyManager;

    /**
     * @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rounding;

    /**
     * @var PromotionExecutor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $promotionExecutor;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    /**
     * @var SubtotalProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->currencyManager = $this->createMock(UserCurrencyManager::class);
        $this->promotionExecutor = $this->createMock(PromotionExecutor::class);
        $this->rounding = $this->createMock(RoundingServiceInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->provider = new SubtotalProvider(
            $this->currencyManager,
            $this->promotionExecutor,
            $this->rounding,
            $this->translator
        );
    }

    public function testGetName()
    {
        $this->assertSame(SubtotalProvider::NAME, $this->provider->getName());
    }

    /**
     * @dataProvider supportedDataProvider
     * @param bool $isSupported
     */
    public function testIsSupported($isSupported)
    {
        $entity = new \stdClass();

        $this->promotionExecutor->expects($this->once())
            ->method('supports')
            ->with($entity)
            ->willReturn($isSupported);
        $this->assertSame($isSupported, $this->provider->isSupported($entity));
    }

    /**
     * @return array
     */
    public function supportedDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }

    public function testGetSubtotal()
    {
        $entity = new \stdClass();

        $discountContext = $this->createMock(DiscountContext::class);
        $discountContext->expects($this->once())
            ->method('getTotalLineItemsDiscount')
            ->willReturn(10);
        $discountContext->expects($this->once())
            ->method('getSubtotalDiscountTotal')
            ->willReturn(5);
        $discountContext->expects($this->once())
            ->method('getShippingDiscountTotal')
            ->willReturn(8);
        $this->promotionExecutor->expects($this->once())
            ->method('execute')
            ->with($entity)
            ->willReturn($discountContext);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($messageId) {
                    return $messageId . ' TRANS';
                }
            );

        $this->rounding->expects($this->exactly(2))
            ->method('round')
            ->willReturnArgument(0);
        $this->currencyManager->expects($this->once())
            ->method('getUserCurrency')
            ->willReturn('EUR');


        $expected = [
            SubtotalProvider::ORDER_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('oro.promotion.discount.subtotal.order.label TRANS', 15.0, 'EUR'),
            SubtotalProvider::SHIPPING_DISCOUNT_SUBTOTAL =>
                $this->createSubtotal('oro.promotion.discount.subtotal.shipping.label TRANS', 8.0, 'EUR')
        ];
        $this->assertEquals($expected, $this->provider->getSubtotal($entity));
    }

    /**
     * @param string $label
     * @param float $amount
     * @param string $currency
     * @return Subtotal
     */
    private function createSubtotal($label, $amount, $currency): Subtotal
    {
        $subtotal = new Subtotal();
        $subtotal->setLabel($label);
        $subtotal->setType(SubtotalProvider::TYPE);
        $subtotal->setVisible(true);
        $subtotal->setAmount($amount);
        $subtotal->setCurrency($currency);
        $subtotal->setOperation(Subtotal::OPERATION_SUBTRACTION);

        return $subtotal;
    }
}
