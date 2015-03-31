<?php

/**
 * ProjectMayhem
 * @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
 */

namespace YMon;

class App {

    static function cacher() {
        return new Util\SCacher(__DIR__ . '/../cache');
    }

} 