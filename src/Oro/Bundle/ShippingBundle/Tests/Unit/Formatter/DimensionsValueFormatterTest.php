<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Formatter;

use Oro\Bundle\ShippingBundle\Formatter\DimensionsValueFormatter;
use Oro\Bundle\ShippingBundle\Model\DimensionsValue;
use Symfony\Component\Translation\TranslatorInterface;

class DimensionsValueFormatterTest extends \PHPUnit\Framework\TestCase
{
    const TRANSLATION_PREFIX = 'oro.length_unit';

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var DimensionsValueFormatter */
    protected $formatter;

    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');

        $this->formatter = new DimensionsValueFormatter($this->translator);
        $this->formatter->setTranslationPrefix(self::TRANSLATION_PREFIX);
    }

    protected function tearDown()
    {
        unset($this->formatter, $this->translator);
    }

    public function testFormatCodeShort()
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.short', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '42 x 42 x 42 translated',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), 'item', true)
        );
    }

    public function testFormatCodeFull()
    {
        $this->translator->expects($this->exactly(2))
            ->method('trans')
            ->willReturnMap(
                [
                    ['N/A', [], null, null, 'N/A_trans'],
                    [static::TRANSLATION_PREFIX . '.item.label.full', [], null, null, 'translated']
                ]
            );

        $this->assertEquals(
            '42 x 42 x 42 translated',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), 'item')
        );
    }

    public function testFormatCodeNullValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(null, 'item')
        );
    }

    public function testFormatCodeEmptyValue()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(DimensionsValue::create(null, null, null), 'item')
        );
    }

    public function testFormatCodeEmptyCode()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('N/A', [], null, null)
            ->willReturn('N/A_trans');

        $this->assertEquals(
            'N/A_trans',
            $this->formatter->formatCode(DimensionsValue::create(42, 42, 42), null)
        );
    }
}
