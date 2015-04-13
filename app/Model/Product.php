<?php

/**
* ProjectMayhem
* @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
*/

namespace YMon\Model;

class Product {

    public $name;
    public $code;

    private $metadata;

    function getMetadata() {

        if (is_null($this->metadata)) {
            $marketAPI = new \YMon\Yandex\MarketAPI();
            $this->metadata = $marketAPI->getProductInfo($this->code);
        }

        return $this->metadata;

    }

    function getPriceAvg() {
        $meta = $this->getMetadata();
        $avg = $meta->model->price->avg;
        return round($avg, 2);
    }
}
