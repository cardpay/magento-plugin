<?php

namespace Cardpay\Core\Config\Model\Config\Backend;

use Exception;
use Magento\Config\Model\Config\Backend\File;
use Magento\Framework\Validation\ValidationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\MediaStorage\Model\File\Uploader;

class Certificate extends File
{
    /**
     * Getter for allowed extensions of uploaded files
     *
     * @return string[]
     */
    protected function _getAllowedExtensions()
    {
        return ['pem'];
    }

    public function beforeSave()
    {
        $file = $this->getFileData();

        if (!empty($file)) {
            try {
                /** @var Uploader $uploader */
                $uploader = $this->_uploaderFactory->create(['fileId' => $file]);
                $uploader->setAllowedExtensions($this->_getAllowedExtensions());
                $uploader->setAllowRenameFiles(true);
                $uploader->addValidateCallback('size', $this, 'validateMaxSize');
                if (!$uploader->checkAllowedExtension($uploader->getFileExtension())) {
                    throw new ValidationException(__('Invalid file format.'));
                }
            } catch (Exception $e) {
                throw new LocalizedException(__('%1', $e->getMessage()));
            }
        }

        return parent::beforeSave();
    }

    /**
     * Receiving uploaded file data
     *
     * @return array
     * @since 100.1.0
     */
    protected function getFileData()
    {
        $path = $this->getPath();
        $newPath = str_replace('payment/cardpay_apay/', 'payment/cardpay_configurations/custom_checkout_apay/', $path);
        $file = [];
        $value = $this->getValue();
        $tmpName = $this->_requestData->getTmpName($newPath);
        if ($tmpName) {
            $file['tmp_name'] = $tmpName;
            $file['name'] = $this->_requestData->getName($newPath);
        } elseif (!empty($value['tmp_name'])) {
            $file['tmp_name'] = $value['tmp_name'];
            $file['name'] = isset($value['value']) ? $value['value'] : $value['name'];
        }

        return $file;
    }
}
