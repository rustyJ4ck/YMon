<?php

/**
 * ProjectMayhem
 * @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
 */

namespace YMon\Currency;

class Cbrf {

  function getUSDRate()
  {
    // simplexml?
    $buff = file_get_contents('http://www.cbr.ru/scripts/XML_daily.asp');

    if ($buff) {
      preg_match(
        '/<CharCode>USD<\/CharCode>.*<Value>([\d\,]+)<\/Value>/Us', $buff, $m
      );
      $usd = str_replace(',','.',$m[1]);
      return round(floatval($usd), 2);

    }
  }
}

