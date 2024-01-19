<?php

namespace Cardpay\Core\Controller\Applepay;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Cardpay\Core\Model\ApayConfigProvider;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Controller\Result\RawFactory;

/**
 * Class ValidateMerchant
 *
 * @package Cardpay\Core\Controller\Applepay
 */
class ValidateMerchant extends Action
{
    /**
     * @var ApayConfigProvider
     */
    protected $apayConfigProvider;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    public function __construct(
        Context $context,
        ApayConfigProvider    $apayConfigProvider,
        Filesystem    $filesystem,
        RawFactory $resultRawFactory
    ) {
        parent::__construct($context);
        $this->apayConfigProvider = $apayConfigProvider;
        $this->filesystem = $filesystem;
        $this->resultRawFactory = $resultRawFactory;
    }

    public function execute()
    {
        $request = $this->getRequest();
        $url = $request->getParam('url');
        $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
        $postData = array(
            'merchantIdentifier' => $this->apayConfigProvider->getMerchantId(),
            'displayName' => $this->getRequest()->getParam('displayName'),
            'domainName' => gethostname(),
            'initiative' => 'web',
            'initiativeContext' => gethostname()
        );
        $postDataFields = json_encode($postData);

        $resultRaw = $this->resultRawFactory->create();
        try {
            $curlOptions = array(
                CURLOPT_URL => $url ? $url : 'https://apple-pay-gateway.apple.com/paymentservices/paymentSession',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postDataFields,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSLCERT => $mediaPath.'unlimit_applepay/'.$this->apayConfigProvider->getMerchantCertificate(),
                CURLOPT_SSLKEY => $mediaPath.'unlimit_applepay/'.$this->apayConfigProvider->getMerchantKey(),
                CURLOPT_SSLCERTPASSWD => '',
                CURLOPT_SSLKEYPASSWD => '',
                CURLOPT_SSLKEYTYPE => 'PEM',
                CURLOPT_SSL_VERIFYPEER => true
            );
            $curlConnection = curl_init();
            curl_setopt_array($curlConnection, $curlOptions);
            $response = curl_exec($curlConnection);
            return $resultRaw->setContents($response);
        } catch (\Exception $e) {
            return $resultRaw->setContents("");
        }
    }
}
