<?php

/**
 * ProjectMayhem
 * @author Golovkin Vladimir <rustyj4ck@gmail.com> http://www.skillz.ru
 */

namespace YMon\Util;

class Logger  {

    private static $_timer;

    public static function dump($v, $t = '') {
        if (self::isDebug()) 
        highlight_string(
            ($t?"[[{$t}]]\n":'') .
            var_export($v, 1) . "\n"
        );
    }

    /**
     * Debug (...args)
     */
    public static function d()
    {
            if (!isset(self::$_timer)) self::$_timer = microtime(1);
            $object = func_get_args();
            if (is_array($object)) {
                if (count($object) > 1)
                    $object = vsprintf(array_shift($object), $object);
                else
                    $object = $object[0];
            }

            $object = sprintf("[%.4f] %s", microtime(1) - self::$_timer, $object);

            if (1||App::isCli()) {
                echo $object, PHP_EOL;
            }
            else {
            \FirePHP::getInstance(true)->log($object); //, $Label);
        }
    }

    /**
     * Execute code only if debugging
     * @param $callable
     */
    public static function run($callable) {
        if (self::isDebug()) {
            $callable();
        }
    }

    public static function isDebug()
    {
        return 1; //App::isDebug();
    }

    private static $_timers = array();

    public static function elapsed($name = 'default', $reset = false)
    {
        if ($reset && isset(self::$_timers[$name])) {
            unset(self::$_timers[$name]);
        }

        $time = 0;

        if (isset(self::$_timers[$name])) {
            $time = microtime(1) - self::$_timers[$name];
            unset(self::$_timers[$name]);
        } else {
            self::$_timers[$name] = microtime(1);
        }

        return $time;
    }
 
}


