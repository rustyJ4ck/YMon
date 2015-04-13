<?php

/**
 * ProjectMayhem
 * @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
 */

namespace YMon\Yandex;

class MarketAPI
{

    private $productRegex = "@require\(\'page\'\)\.setData\((?P<payload>.*)\)\.init@U";
    private $productAvgRegex = '@class="product-card__price-value">(?P<payload>[\d \.\,]+)@';

    function getProductInfo($productID)
    {

        $cacher = \YMon\App::cacher();

        $url = 'http://market.yandex.ru/product/' . $productID . '/';
        $id  = $productID;

        $cachedMetadata = $cacher->get('ym', $id);

        if (!$cachedMetadata) {
            $buffer   = file_get_contents($url);
            $metadata = null;
            if (preg_match($this->productRegex, $buffer, $matches)) {
                $metadata = json_decode($matches['payload']);

                if (!isset($metadata->model->price->avg)) {
                    if (preg_match($this->productAvgRegex, $buffer, $matches)) {
                        $metadata->model->price->avg = floatval(preg_replace('@[^\d]@', '', $matches['payload']));
                    } else {
                        // you nooo avg?
                        $metadata->model->price->avg = ($metadata->model->price->min + $metadata->model->price->max) / 2;
                    }
                }
            }

            $cacher->set('ym', $id, $metadata, 3600);
        } else {
            $metadata = $cachedMetadata->value();
        }

        return $metadata;

    }

}


/*

->model=

class stdClass#284 (5) {
  public $id =>
  string(7) "9275006"
  public $name =>
  string(11) "LG F-80C3LD"
  public $categoryId =>
  string(5) "90566"
  public $images =>
  class stdClass#6430 (2) {
    public $all =>
    array(2) {
      [0] =>
      class stdClass#6421 (3) {
        ...
      }
      [1] =>
      class stdClass#79 (3) {
        ...
      }
    }
    public $small =>
    class stdClass#60 (3) {
      public $src =>
      string(74) "//mdata.yandex.net/i?path=b02
      public $width =>
      string(3) "139"
      public $height =>
      string(3) "139"
    }
  }
  public $price =>
  class stdClass#294 (3) {
    public $min =>
    string(5) "15627"
    public $max =>
    string(5) "25248"
    public $currency =>
    string(3) "RUR"
  }
}

*/