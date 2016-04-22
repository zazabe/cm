<?php

class CM_Asset_Css extends CM_Asset_Abstract {

    /** @var CM_Frontend_Render */
    protected $_render;

    /** @var string|null */
    protected $_content;

    /** @var string|null */
    private $_prefix;

    /** @var CM_Asset_Css[] */
    private $_children = array();

    /** @var string|null */
    private $_autoprefixerBrowsers = null;

    /**
     * @param CM_Frontend_Render $render
     * @param string|null        $content
     * @param array|null         $options
     */
    public function __construct(CM_Frontend_Render $render, $content = null, array $options = null) {
        $this->_render = $render;
        if (null !== $content) {
            $this->_content = (string) $content;
        }
        $options = (array) $options;
        if (isset($options['prefix'])) {
            $this->_prefix = (string) $options['prefix'];
        }
        if (isset($options['autoprefixerBrowsers'])) {
            $this->_autoprefixerBrowsers = (string) $options['autoprefixerBrowsers'];
        }
    }

    /**
     * @param string      $content
     * @param string|null $prefix
     */
    public function add($content, $prefix = null) {
        $this->_children[] = new self($this->_render, $content, ['prefix' => $prefix]);
    }

    public function get($compress = null) {
        $content = $this->_getContent();
        return $this->_compile($content, $compress);
    }

    public function addVariables() {
        foreach (array_reverse($this->_render->getSite()->getModules()) as $moduleName) {
            foreach (array_reverse($this->_render->getSite()->getThemes()) as $theme) {
                $file = new CM_File($this->_render->getThemeDir(true, $theme, $moduleName) . 'variables.less');
                if ($file->exists()) {
                    $this->add($file->read());
                }
            }
        }
    }

    protected function _getContent() {
        $content = '';
        if ($this->_prefix) {
            $content .= $this->_prefix . ' {' . PHP_EOL;
        }
        if ($this->_content) {
            $content .= $this->_content . PHP_EOL;
        }
        foreach ($this->_children as $css) {
            $content .= $css->_getContent();
        }
        if ($this->_prefix) {
            $content .= '}' . PHP_EOL;
        }
        return $content;
    }

    /**
     * @param string       $content
     * @param boolean|null $compress
     * @return string
     */
    private function _compile($content, $compress = null) {
        $content = (string) $content;
        $compress = (bool) $compress;

        $cacheKey = CM_CacheConst::App_Resource . '_md5:' . md5($content);
        $cacheKey .= '_compress:' . (int) $compress;
        $cache = new CM_Cache_Storage_File();
        if (false === ($contentTransformed = $cache->get($cacheKey))) {
            $contentTransformed = $content;
            $contentTransformed = $this->_compileLess($contentTransformed, $compress, $cache);
            $contentTransformed = $this->_compileAutoprefixer($contentTransformed);
            $contentTransformed = trim($contentTransformed);
            $cache->set($cacheKey, $contentTransformed);
        }
        return $contentTransformed;
    }

    /**
     * @param string                    $content
     * @param bool                      $compress
     * @param CM_Cache_Storage_Abstract $cache
     * @return string
     */
    private function _compileLess($content, $compress, CM_Cache_Storage_Abstract $cache) {
        $render = $this->_render;

        $lessCompiler = new lessc();
        $lessCompiler->registerFunction('image', function ($arg) use ($render) {
            /** @var CM_Frontend_Render $render */
            list($type, $delimiter, $values) = $arg;
            return array('function', 'url', array('string', $delimiter, array($render->getUrlResource('layout', 'img/' . $values[0]))));
        });
        $lessCompiler->registerFunction('image-inline', function ($arg) use ($render, $cache) {
            /** @var CM_Frontend_Render $render */
            list($type, $delimiter, $values) = $arg;
            if (2 == sizeof($values) && is_array($values[0]) && is_array($values[1])) {
                $delimiter = (string) $values[0][1];
                $path = (string) $values[0][2][0];
                $size = (int) $values[1][1];
            } else {
                $path = $values[0];
            }

            $imagePath = $render->getLayoutPath('resource/img/' . $path, null, null, true, true);
            $cacheKey = CM_CacheConst::App_Resource . '_md5:' . md5($imagePath);
            if (!empty($size)) {
                $cacheKey .= '_size:' . $size;
            }
            if (false === ($imageBase64 = $cache->get($cacheKey))) {
                $file = new CM_File($imagePath);
                $img = new CM_Image_Image($file->read());
                if (!empty($size)) {
                    $img->resize($size, $size);
                }
                $img->setFormat(CM_Image_Image::FORMAT_GIF);
                $imageBase64 = base64_encode($img->getBlob());
                $cache->set($cacheKey, $imageBase64);
            }
            $url = 'data:image/gif;base64,' . $imageBase64;
            return array('function', 'url', array('string', $delimiter, array($url)));
        });
        $lessCompiler->registerFunction('urlFont', function ($arg) use ($render) {
            /** @var CM_Frontend_Render $render */
            list($type, $delimiter, $values) = $arg;
            return array($type, $delimiter, array($render->getUrlStatic('/font/' . $values[0])));
        });
        if ($compress) {
            $lessCompiler->setFormatter('compressed');
        }
        return $lessCompiler->compile($content);
    }

    /**
     * @param string $content
     * @return string
     */
    private function _compileAutoprefixer($content) {
        $command = 'autoprefixer';
        $args = [];
        if (null !== $this->_autoprefixerBrowsers) {
            $args[] = '--browsers';
            $args[] = $this->_autoprefixerBrowsers;
        }
        return CM_Util::exec($command, $args, $content);
    }
}
