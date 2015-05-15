<?php

/**
 * ProjectMayhem
 * @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
 */

namespace YMon\Yandex;

class MarketAPI
{

    private $debug = false;

    private static $cookies;
    private static $counter = 0;

    private $sleep = 2;

    private $productRegex = "@require\(\'page\'\)\.setData\((?P<payload>.*)\)\.init@U";
    private $productAvgRegex = '@class="product-card__price-value">(?P<payload>[\d \.\,]+)@';

    private function fetch($url) {

      self::$counter++;

      $cookieHeader = '';

      if (isset(self::$cookies)) {
        $cookieHeader = 'Cookie: ';
        foreach (self::$cookies as $cName => $cValue) {
          $cookieHeader .= ($cName . '=' . $cValue . '; ');
        }
        $cookieHeader .= "\r\n";
      }

      $opts = array(
        'http'=>array(
          'method'=>"GET",
          'header'=>	"Host: market.yandex.ru\r\n" .
            "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:37.0) Gecko/20150219 Firefox/27.0.1\r\n" .
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
            "Accept-language: en-us,en,ru-ru,ru;q=0.5\r\n" .
            "Accept-Charset: ISO-8859-1,UTF-8;q=0.7,*;q=0.7\r\n" .
            $cookieHeader .
            "Referer: {$url}\r\n"
        )
      );

      \YMon\Util\Logger::d('FETCH: %s HEADERS %s', $url, ($this->debug ? $opts['http']['header'] : '!'));

      $context = stream_context_create($opts);
      $return = file_get_contents($url, FALSE , $context);

      if ($this->debug) {
        file_put_contents(
          'debug/' . self::$counter . '.html',
          $return . ' <!-- ' . $opts['http']['header'] . '-->'
        );
      }

      if (!isset(self::$cookies)) {
        self::$cookies = array();
      }

      foreach ($http_response_header as $hdr) {
        if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
          parse_str($matches[1], $tmpCookie);
          if ($tmpCookie) {
            self::$cookies = array_merge(self::$cookies, $tmpCookie);
          }
        }
      }

      return $return;
    }

    function getProductInfo($productID)
    {

        $cacher = \YMon\App::cacher();

        $url = 'http://market.yandex.ru/product/' . $productID . '/';
        $id  = $productID;

        $cachedMetadata = $cacher->get('ym', $id);

        if (!$cachedMetadata || ! $cachedMetadata->value()) {

            $buffer   = $this->fetch($url);

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

                $cacher->set('ym', $id, $metadata, 3600);
            }

            // do not spam!
            if ($this->sleep) {
               sleep($this->sleep);
            }


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