<?php
/**
 * Dhl Shipping
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to
 * newer versions in the future.
 *
 * PHP version 7
 *
 * @package   Dhl\Shipping\Test\Unit
 * @author    Christoph Aßmann <christoph.assmann@netresearch.de>
 * @copyright 2018 Netresearch GmbH & Co. KG
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/
 */
namespace Dhl\Shipping\Observer;

use Dhl\Shipping\Model\Config\ModuleConfig;
use Dhl\Shipping\Model\Config\ModuleConfigInterface;
use Dhl\Shipping\Model\Service\Availability\CodAvailability;
use Dhl\Shipping\Util\ShippingProducts\ShippingProductsInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\OfflinePayments\Model\Cashondelivery;
use Magento\OfflinePayments\Model\Checkmo;
use Magento\Quote\Model\Quote;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * DisableCodPaymentObserverTest
 *
 * @package  Dhl\Shipping\Test\Unit
 * @author   Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @author   Christoph Aßmann <christoph.assmann@netresearch.de>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     http://www.netresearch.de/
 */
class DisableCodPaymentObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var ModuleConfigInterface|MockObject
     */
    private $config;

    /**
     * @var CheckoutSession|MockObject
     */
    private $checkoutSession;

    /**
     * @var CodAvailability|MockObject
     */
    private $codAvailability;

    /**
     * @var ShippingProductsInterface|MockObject
     */
    private $shippingProducts;

    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = new ObjectManager($this);

        $this->config = $this->getMockBuilder(ModuleConfig::class)
            ->setMethods(['getShipperCountry', 'canProcessShipping', 'isCodPaymentMethod', 'getEuCountryList'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(CheckoutSession::class)
            ->setMethods(['start', 'getQuote', 'destroy'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->codAvailability = $this->getMockBuilder(CodAvailability::class)
                                      ->disableOriginalConstructor()
                                      ->getMock();

        $this->shippingProducts = $this->getMockBuilder(ShippingProductsInterface::class)->getMock();
    }

    /**
     * @test
     */
    public function skipDisabledPaymentMethod()
    {
        /** @var DisableCodPaymentObserver $codObserver */
        $codObserver = $this->objectManager->getObject(DisableCodPaymentObserver::class);

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getData')->with('result', null)->willReturn($resultMock);
        $resultMock->expects($this->once())->method('getData')->with('is_available', null)->willReturn(false);

        $observerMock->expects($this->never())
            ->method('getData')
            ->with('quote', null);

        $codObserver->execute($observerMock);
    }

    /**
     * @test
     */
    public function quoteUnavailable()
    {
        /** @var DisableCodPaymentObserver $codObserver */
        $codObserver = $this->objectManager->getObject(DisableCodPaymentObserver::class, [
            'config' => $this->config,
            'checkoutSession' => $this->checkoutSession,
        ]);

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->once())->method('getEvent')->willReturn($eventMock);
        $eventMock->expects($this->once())->method('getData')->with('result', null)->willReturn($resultMock);
        $resultMock->expects($this->once())->method('getData')->with('is_available', null)->willReturn(true);

        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote', null);

        $this->checkoutSession
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn(null);

        $this->config
            ->expects($this->never())
            ->method('canProcessShipping');

        $codObserver->execute($observerMock);
    }

    /**
     * @test
     */
    public function observerReturnsVoidWhenShipmentIsNotProcessedWithDhl()
    {
        /** @var DisableCodPaymentObserver $codObserver */
        $codObserver = $this->objectManager->getObject(DisableCodPaymentObserver::class, [
            'config' => $this->config,
            'checkoutSession' => $this->checkoutSession,
        ]);

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodMock = $this->getMockBuilder(Cashondelivery::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $returnValueMap = [
            ['result', null, $resultMock],
            ['method_instance', null, $paymentMethodMock],
        ];
        $eventMock->expects($this->exactly(2))->method('getData')->willReturnMap($returnValueMap);
        $resultMock->expects($this->once())->method('getData')->with('is_available', null)->willReturn(true);

        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote', null);

        $quote = new DataObject(['shipping_address' => new DataObject(['shipping_method' => 'flatrate_flatrate'])]);
        $this->checkoutSession
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->config
            ->expects($this->once())
            ->method('canProcessShipping')
            ->willReturn(false);

        $this->config
            ->expects($this->never())
            ->method('isCodPaymentMethod');

        $codObserver->execute($observerMock);
    }

    /**
     * @test
     */
    public function observerReturnsVoidWhenPaymentIsNotCod()
    {
        /** @var DisableCodPaymentObserver $codObserver */
        $codObserver = $this->objectManager->getObject(DisableCodPaymentObserver::class, [
            'config' => $this->config,
            'checkoutSession' => $this->checkoutSession,
            'codAvailability' => $this->codAvailability
        ]);

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodMock = $this->getMockBuilder(Checkmo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $returnValueMap = [
            ['result', null, $resultMock],
            ['method_instance', null, $paymentMethodMock],
        ];
        $eventMock->expects($this->exactly(2))->method('getData')->willReturnMap($returnValueMap);
        $resultMock->expects($this->once())->method('getData')->with('is_available', null)->willReturn(true);

        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote', null);

        $quote = new DataObject(['shipping_address' => new DataObject(['shipping_method' => 'flatrate_flatrate'])]);
        $this->checkoutSession
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->config
            ->expects($this->once())
            ->method('canProcessShipping')
            ->willReturn(true);

        $this->config
            ->expects($this->once())
            ->method('isCodPaymentMethod')
            ->willReturn(false);

        $this->config
            ->expects($this->never())
            ->method('getShipperCountry');

        $codObserver->execute($observerMock);
    }

    /**
     * @test
     */
    public function codIsNotAvailable()
    {
        $recipientCountry = 'PL';

        /** @var DisableCodPaymentObserver $codObserver */
        $codObserver = $this->objectManager->getObject(DisableCodPaymentObserver::class, [
            'config' => $this->config,
            'checkoutSession' => $this->checkoutSession,
            'codAvailability' => $this->codAvailability
        ]);

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodMock = $this->getMockBuilder(Checkmo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $returnValueMap = [
            ['result', null, $resultMock],
            ['method_instance', null, $paymentMethodMock],
        ];
        $eventMock->expects($this->exactly(2))->method('getData')->willReturnMap($returnValueMap);
        $resultMock->expects($this->once())->method('getData')->with('is_available', null)->willReturn(true);

        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote', null);

        $shippingAddress = new DataObject([
            'shipping_method' => 'flatrate_flatrate',
            'country_id' => $recipientCountry,
        ]);
        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getShippingAddress')->willReturn($shippingAddress);
        $this->checkoutSession
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->config
            ->expects($this->once())
            ->method('canProcessShipping')
            ->willReturn(true);

        $this->config
            ->expects($this->once())
            ->method('isCodPaymentMethod')
            ->willReturn(true);

        $codObserver->execute($observerMock);
    }

    /**
     * @test
     */
    public function codIsAvailable()
    {
        $recipientCountry = 'DE';

        /** @var DisableCodPaymentObserver|MockObject $codObserver */
        $codObserver = $this->objectManager->getObject(DisableCodPaymentObserver::class, [
            'config' => $this->config,
            'checkoutSession' => $this->checkoutSession,
            'codAvailability' => $this->codAvailability
        ]);

        /** @var Observer|MockObject $observerMock */
        $observerMock = $this->getMockBuilder(Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $eventMock = $this->getMockBuilder(Event::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(DataObject::class)
            ->setMethods(['getData', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();
        $paymentMethodMock = $this->getMockBuilder(Checkmo::class)
            ->disableOriginalConstructor()
            ->getMock();

        $observerMock->expects($this->exactly(2))->method('getEvent')->willReturn($eventMock);

        $returnValueMap = [
            ['result', null, $resultMock],
            ['method_instance', null, $paymentMethodMock],
        ];
        $eventMock->expects($this->exactly(2))->method('getData')->willReturnMap($returnValueMap);
        $resultMock->expects($this->once())->method('getData')->with('is_available', null)->willReturn(true);

        $observerMock->expects($this->once())
            ->method('getData')
            ->with('quote', null);

        $shippingAddress = new DataObject([
            'shipping_method' => 'flatrate_flatrate',
            'country_id' => $recipientCountry,
        ]);

        $quote = $this->getMockBuilder(Quote::class)->disableOriginalConstructor()->getMock();
        $quote->method('getShippingAddress')->willReturn($shippingAddress);
        $this->checkoutSession
            ->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);

        $this->config
            ->expects($this->once())
            ->method('canProcessShipping')
            ->willReturn(true);

        $this->config
            ->expects($this->once())
            ->method('isCodPaymentMethod')
            ->willReturn(true);

        $this->codAvailability->expects($this->once())->method('isCodAvailable')->willReturn(true);

        $resultMock->expects($this->once())->method('setData')->with('is_available', true);
        $codObserver->execute($observerMock);
    }
}
