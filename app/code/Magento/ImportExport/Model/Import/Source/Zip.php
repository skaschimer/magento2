<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ImportExport\Model\Import\Source;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\ValidatorException;

/**
 * Zip import adapter.
 */
class Zip extends Csv
{
    /**
     * @param string $file
     * @param \Magento\Framework\Filesystem\Directory\Write $directory
     * @param string $options
     * @param \Magento\Framework\Archive\Zip|null $zipArchive
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function __construct(
        $file,
        \Magento\Framework\Filesystem\Directory\Write $directory,
        $options,
        ?\Magento\Framework\Archive\Zip $zipArchive = null
    ) {
        $zip = $zipArchive ?? ObjectManager::getInstance()->get(\Magento\Framework\Archive\Zip::class);
        $csvFile = $zip->unpack(
            $file,
            preg_replace('/\.zip$/i', '.csv', $file)
        );
        if (!$csvFile) {
            throw new ValidatorException(__('Sorry, but the data is invalid or the file is not uploaded.'));
        }
        $directory->delete($directory->getRelativePath($file));

        try {
            parent::__construct($csvFile, $directory, $options);
        } catch (\LogicException $e) {
            $directory->delete($directory->getRelativePath($csvFile));
            throw $e;
        }
    }
}
