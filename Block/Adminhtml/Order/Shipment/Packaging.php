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
 * @package   Dhl\Shipping\Block
 * @author    Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @copyright 2018 Netresearch GmbH & Co. KG
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.netresearch.de/
 */
namespace Dhl\Shipping\Block\Adminhtml\Order\Shipment;

use Dhl\Shipping\Model\Config\ModuleConfigInterface;
use Dhl\Shipping\Util\Escaper;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Block\Adminhtml\Order\Packaging as MagentoPackaging;
use Magento\Shipping\Model\Carrier\Source\GenericInterface;
use Magento\Shipping\Model\CarrierFactory;
use Magento\Store\Model\ScopeInterface;
use Zend_Measure_Weight;

/**
 * Packaging
 *
 * @package  Dhl\Shipping\Block
 * @author   Sebastian Ertner <sebastian.ertner@netresearch.de>
 * @license  http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link     http://www.netresearch.de/
 */
class Packaging extends MagentoPackaging
{
    /**
     * @var ModuleConfigInterface
     */
    private $moduleConfig;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * Packaging constructor.
     *
     * @param Context               $context
     * @param EncoderInterface      $jsonEncoder
     * @param GenericInterface      $sourceSizeModel
     * @param Registry              $coreRegistry
     * @param CarrierFactory        $carrierFactory
     * @param ModuleConfigInterface $moduleConfig
     * @param Escaper               $escaper
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        EncoderInterface $jsonEncoder,
        GenericInterface $sourceSizeModel,
        Registry $coreRegistry,
        CarrierFactory $carrierFactory,
        ModuleConfigInterface $moduleConfig,
        Escaper $escaper,
        array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->moduleConfig = $moduleConfig;
        $this->escaper = $escaper;

        parent::__construct($context, $jsonEncoder, $sourceSizeModel, $coreRegistry, $carrierFactory, $data);
    }

    /**
     * @return bool
     */
    public function displayCustomsValue()
    {
        $destCountryId = $this->getShipment()->getShippingAddress()->getCountryId();

        return $this->moduleConfig->isCrossBorderRoute($destCountryId, $this->getShipment()->getStoreId());
    }

    /**
     * @return mixed
     */
    public function getStoreWeightUnit()
    {
        $weightUnit = strtoupper($this->scopeConfig->getValue(
            'general/locale/weight_unit',
            ScopeInterface::SCOPE_STORE,
            $this->getShipment()->getStoreId()
        ));

        return $weightUnit;
    }

    /**
     * @return bool
     */
    public function isMetricUnit()
    {
        $unit = $this->getStoreWeightUnit();
        return $unit != Zend_Measure_Weight::LBS;
    }

    /**
     * @return mixed
     */
    public function getDefaultExportContentType()
    {
        $storeId = $this->getShipment()->getStoreId();
        return $this->moduleConfig->getDefaultExportContentType($storeId);
    }

    /**
     * @return mixed
     */
    public function getDefaultExportContentTypeExplanation()
    {
        $storeId = $this->getShipment()->getStoreId();
        return $this->moduleConfig->getDefaultExportContentTypeExplanation($storeId);
    }

    /**
     * @return array
     */
    public function getContainers()
    {
        $order = $this->getShipment()->getOrder();
        $storeId = $this->getShipment()->getStoreId();
        $address = $order->getShippingAddress();
        $carrier = $this->_carrierFactory->create($order->getShippingMethod(true)->getCarrierCode(), $storeId);
        $countryShipper = $this->_scopeConfig->getValue(
            Shipment::XML_PATH_STORE_COUNTRY_ID,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        if ($carrier) {
            $params = new DataObject(
                [
                    'method'            => $order->getShippingMethod(true)->getMethod(),
                    'country_shipper'   => $countryShipper,
                    'country_recipient' => $address->getCountryId(),
                ]
            );
            return $carrier->getContainerTypes($params);
        }
        return [];
    }

    /**
     * Escape a string for the HTML attribute context.
     *
     * @param string  $string
     * @param boolean $escapeSingleQuote
     *
     * @return string
     */
    public function escapeHtmlAttr($string, $escapeSingleQuote = true)
    {
        return $this->escaper->escapeHtmlAttr($string, $escapeSingleQuote);
    }
}
