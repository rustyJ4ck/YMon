<?php

/**
 * ProjectMayhem
 * @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
 */

namespace YMon;

use YMon\Model\Product;
use YMon\Util\Logger;

class YMon {

	private $sheetsPath;

	function __construct($root = false) {
		$this->sheetsPath = $root ? $root : (__DIR__ . '/../sheets');
		$this->sheetsPath .= '/';
	}

    function process() {
        $this->listBooks();
    }

	function listBooks() {
		foreach (glob($this->sheetsPath . '*.xlsx') as $file) {

            $book = null;

            try {
			    $book = $this->getBook($file);
            }
            catch (\PHPExcel_Reader_Exception $e) {
                Logger::d('Error: %s', $e->getMessage());
            }

            if ($book) {
                if ($rows = $this->parseBook($book)) {

                    if ($rows) {
                        Logger::d('Updated rows: %d', $rows);
                        $this->saveExcel($book, $file);
                    }
                }
            }
		}
	}

    /**
     * @param \PHPExcel $book
     */
	function parseBook($book) {

        $products = [];

		$sheet = $book->setActiveSheetIndexByName('Products');

        // Build products list

        /** @var \PHPExcel_Worksheet_RowIterator $row */
        foreach ($sheet->getRowIterator() as $row) {
            $cells = $row->getCellIterator();
            $product = null;
            /** @var \PHPExcel_Cell $cell */
            foreach ($cells as  $cell) {

                $value = $cell->getValue();

                if ($cell->getRow() > 1 && $value) {

                    $product = $product ?: new Product();

                    switch ($cell->getColumn()) {
                        case 'A':
                            $product->name = $value;
                            break;
                        case 'B':
                            $product->code = $value;
                            break;
                    }
                }

            }

            if ($product) $products [$product->code]= $product;
        }

        // Fetch prices

        /** @var Product $product */
        /*
        foreach ($products as $product) {
            Logger::d('product: [%10s] %s', $product->code, $product->name);
            $meta = $product->getMetadata();
            if ($meta->model) {
                Logger::d('... %.2f --> %.2f', $meta->model->price->min, $meta->model->price->max);
            }
        }
        */

        // Update excel

        $sheet = $book->setActiveSheetIndexByName('Prices');

        $rowID = 2;

        $prevCell = null;

        $updated = 0;

        while (1) {
            $cell = $sheet->getCellByColumnAndRow(0, $rowID);

            if (!$cell->getValue()) {

                $uptodate = false;

                // update if >= 6hours sinse last update
                if ($prevCell) {
                    $now = new \DateTime();
                    $diff = $now->diff(new \DateTime("@" . \PHPExcel_Shared_Date::ExcelToPHP($prevCell->getValue())));
                    if ($diff->h < 6) $uptodate = true;
                    Logger::d('lastupd: %d:%d HM [%s]', $diff->h, $diff->i, ($uptodate ? '-' : '+'));
                }

                // Update!
                if (!$uptodate) {
                    $updated++;
                    $this->updateProductsRow($sheet, $products, $rowID);
                }

                break;
            }

            $prevCell = $cell;

            $rowID++;

        }

        // update done
        return $updated;
    }

    /**
     * @param \PHPExcel_Worksheet $sheet
     * @param $products
     */
    function updateProductsRow($sheet, $products, $rowID) {


        // $sheet->insertNewRowBefore($rowID, 1);
        // $rowID++;

        $column = 1;

        $cell = $sheet->getCellByColumnAndRow(0, $rowID);

        $cell->setValue(\PHPExcel_Shared_Date::PHPToExcel(time(), false))
            ->getStyle()
            ->getNumberFormat()
            ->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY);

        foreach ($products as $product) {
            $cell = $sheet->getCellByColumnAndRow($column, $rowID);
            $avg = $product->getPriceAvg();
            $cell->setValue($avg);
            Logger::d('.. %-25s  %.2f', $product->name, $avg);
            $column++;
        }

        /*
        $stationNameCell = $resultSheet->getCellByColumnAndRow(3, $currentRow + $i);

        $stationNameCell->setDataType(PHPExcel_Cell_DataType::TYPE_STRING2);
        $stationNameCell->getStyle()->getFont()->setColor(new PHPExcel_Style_Color(PHPExcel_Style_Color::COLOR_DARKBLUE));
        $stationNameCell->setValue(
        //($currentRow+$i) . ' : ' . $i . ' | ' . $stationRows . ' ' .
            $stations[$i]['FULLNAME']
        )->getHyperlink()->setUrl($url);

        */

    }

    /**
     * @param $filename
     * \PHPExcel_Reader_Excel2007
     */
    function getBook($filename) {
        // $cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
        $cacheMethod = \PHPExcel_CachedObjectStorageFactory::cache_in_memory;
        $cacheSettings = array( 'memoryCacheSize' => '8MB');
        \PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);

        $book = $this->readExcel($filename);

        return $book;
	}

    function readExcel($inputFileName) {
        Logger::d('XLS.read: %s', $inputFileName);
        $objPHPExcel = \PHPExcel_IOFactory::load($inputFileName);
        return $objPHPExcel;
    }

    function saveExcel($excel, $file) {

        Logger::d('XLS.Save: %s', $file);
        $type = preg_match('@\.xlsx$@', $file) ? 'Excel2007' : 'Excel5';
        $writer = \PHPExcel_IOFactory::createWriter($excel, $type);
        $writer->save($file);
    }




}
