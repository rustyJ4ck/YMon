<?php

namespace YMon\Util;

/**
 * @package    Sape
 * @author     Golovkin Vladimir <r00t@skillz.ru> http://www.skillz.ru
 * @version    $Id: sape_cacher.php,v 1.2.4.3 2014/01/14 09:03:49 Vova Exp $
 */


/**
 * Cache part
 */
class SCacherItem {

    public $data;
    public $created;
    public $till;       // 0 - unlimited
    public $key;

    function __construct($data, $s = false) {
        if ($s) $data = unserialize($data);

        $created = isset($data['created']) ? $data['created'] : time();
        $till    =  isset($data['till']) ? $data['till'] : 0;

        $this->data       = $data['data'];
        $this->created    = $created;
        $this->till       = $till;
        $this->key        = @$data['key'];
    }

    function serialize() {
        return serialize(array(
                'data'        => $this->data
            , 'created'     => $this->created
            , 'till'        => $this->till
            , 'key'         => $this->key
            ));
    }

    /**
     * Cache item is expired
     */
    function isExpired() {
        return (SCacher::UNLIMITED != $this->till) && (time() > ($this->created + $this->till));
    }

    function value() {
        return $this->data;
    }

}

/**
 * Class SCacherException
 * @package InfLK\Util
 */
class SCacherException extends \Exception {

}

/**
 * Cacher
 */
class SCacher {

    const UNLIMITED = 0;
    const NOTFOUND  = null;

    private $_base_dir;
    private $_use_compression = false;

    /** @var int extra subdir for large cache */
    protected $subdir_length = 1;

    function __construct($base = null) {

        if (!$base)
            $this->setRoot(__DIR__ . '/cache');
        else
            $this->setRoot($base);
    }

    /**
     * To disable chroot
     * [lib_sape_cacher]
     * root = cache
     *
     * @param mixed $config
     */
    function configure($config) {

        if (isset($config['root'])) {
            $this->setRoot($config['root']);
        }

        if (isset($config['subdir_length'])) {
            $this->subdir_length = $config['subdir_length'];
        }
    }

    /**
     * @param string root with trail slash!
     */
    function setRoot($base, $absolute = false) {

        if (!(substr($base, -1, 1) == '/' || substr($base, -1, 1) == DIRECTORY_SEPARATOR)) {
            $base .= DIRECTORY_SEPARATOR;
        }

        if (!$absolute)
            $this->_base_dir = $base . /*DIRECTORY_SEPARATOR  .*/ 'opcacher' . DIRECTORY_SEPARATOR;
        else
            $this->_base_dir = $base;

        return $this;
    }

    function getRoot() { return $this->_base_dir; }

    function use_compression($f = null) {
        if ($f === null) return $this->_use_compression;
        else $this->_use_compression = $f;
        return $this;
    }

    private function _id($id) {
        return sprintf('%u',crc32($id));
    }

    /**
     * make path
     * path may be "namespace/namespace/gid"
     */
    private function _path($gid, $id) {
        $id = $this->_id($id);

        if ($this->subdir_length) {
            $gid .= DIRECTORY_SEPARATOR;
            $gid .= substr($id, 0, $this->subdir_length);
        }

        /* watchout! */
        if (!is_dir($this->_base_dir . $gid)) mkdir($this->_base_dir . $gid, 0777, true);
        return $this->_base_dir . $gid . DIRECTORY_SEPARATOR . $id;
    }

    /**
     * Set cache
     * gid may be like path id "/this/is/good"
     *
     * @param mixed $gid
     * @param mixed $id
     * @param mixed $data
     * @param mixed $time +seconds
     * @return self
     */

    function set($gid, $id, $data, $time = self::UNLIMITED) {
        $cdata = new SCacherItem(array(
            'data'        => $data
        , 'till'        => $time
        , 'key'         => $id
        ));
        $path = $this->_path($gid, $id);
        $_data = $this->_use_compression ? gzcompress($cdata->serialize()) : $cdata->serialize();

        /** если путь не существует, он будет создан */
        if (false === file_put_contents($path, $_data, LOCK_EX)) {
            throw new ScacherException('[cacher] set fail: ' . $path);
        }

        return $this;
    }

    private $_from_cache = false;

    function isCached() {
        return $this->_from_cache;
    }

    /**
     * Get
     * @return SCacherItem warn! self::NOTFOUND
     */
    function get($gid, $id) {
        $path = $this->_path($gid, $id);
        $this->get_from_file($path);
        return $this->_last_data;
    }

    /**
     * Get
     * @return mixed warn! self::NOTFOUND
     */
    function getValue($gid, $id) {
        $path = $this->_path($gid, $id);
        return $this->get_from_file($path);
    }


    /** @private SCacherItem */
    private $_last_data;

    function get_from_file($path) {

        $return = self::NOTFOUND;
        $this->_from_cache = false;
        $this->_last_data = null;

        if (!file_exists($path)) return $return;

        $_data = file_get_contents($path);

        // corrupted file or something
        if (empty($_data)) {
            return $return;
        }

        if ($this->_use_compression) {
            $_data = gzuncompress($_data);
        }

        $data = new SCacherItem($_data, true);

        if ($data->isExpired())  {
            @unlink($path);
        }
        else {
            $this->_last_data = $data;
            $return = $data->data;
            $this->_from_cache = true;
        }

        return $return;
    }

    /**
     * @return array multy data
     */
    function get_multy($gid) {
        $result = array();
        $dir = $this->_base_dir . $gid;
        if (!is_dir($dir)) return false;
        $files = glob($dir . '/*');
        foreach ($files as $v) {
            if (is_file($v)) {
                if ($c = $this->get_from_file($v)) {
                    if (empty($this->_last_data->key))
                        $result[] = $c;
                    else
                        $result[$this->_last_data->key] = $c;
                }
            }
        }
        return $result;
    }

    /**
     * Cleanup for gid
     */
    function cleanup($gid) {
        $dir = $this->_base_dir . $gid . '/';
        if (empty($gid) || !is_dir($dir)) return false;

        $dirs = $this->_gc_files($dir);
        $counter = 0;

        foreach ($dirs as $_dir => $dir) {
            foreach ($dir as $file) {
                @unlink($file);
                //core::dprint('[cacher cleaunp]' . $file, core::E_TRACE);
                $counter++;
            }
            @unlink($_dir);
            //core::dprint('[cacher cleaunp]' . $_dir, core::E_TRACE);
        }
        return $counter;
    }

    /**
     * Enum dirs (called from enum files)
     */
    function _gc_dirs($dir = null) {
        $dir = $dir ? $dir : $this->_base_dir;
        $dirs = glob($dir . '*', GLOB_ONLYDIR);
        foreach ($dirs as $k => $v) {
            if (substr($v, -3) == 'CVS'
                || substr($v, -11) == 'credentials')
                unset($dirs[$k]);
        }
        return $dirs;
    }

    /**
     * Enum files
     */
    function _gc_files($dir = null) {
        $files = array();
        $dirs = $this->_gc_dirs($dir);
        foreach ($dirs as $dir) {
            $_files = glob($dir . DIRECTORY_SEPARATOR . '*');
            foreach ($_files as $i => $file) if (substr($file, -3) == 'CVS') unset($_files[$i]);
            $files[$dir] = $_files;
        }
        return $files;
    }

    /**
     * garbage collector
     * @param bool remove only expired
     * @return integer deleted files count
     */
    function gc($only_expired = true) {
        $counter = 0;
        $files = $this->_gc_files();
        foreach ($files as $dir) {
            foreach ($dir as $file) {
                $is_remove = true;
                if ($only_expired) {
                    $ci = new SCacherItem(file_get_contents($file), true);
                    $is_remove = $ci->isExpired();
                }
                if ($is_remove)  {
                    @unlink($file);
                    $counter++;
                }
            }
        }
        return $counter;
    }
}