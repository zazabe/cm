<?php

class CM_Frontend_JavascriptContainer_Bundle {

    /** @var array */
    protected $_content = [];

    /** @var string[] */
    protected $_entryPath = [];

    /** @var string[] */
    protected $_sourcePath = [];

    /** @var string[] */
    protected $_rawPath = [];

    /** @var array */
    protected $_libraryPath = [];

    /** @var array */
    protected $_sourceMapping = [];

    /**
     * CM_Frontend_JavascriptContainer_Bundle constructor.
     */
    public function __construct() {
    }

    /**
     * @param string     $command code|sourcemaps
     * @param array|null $options
     * @return string
     * @throws CM_Exception_Invalid
     */
    public function compile($command, array $options = null) {
        if (!in_array($command, ['code', 'sourcemaps', 'checksum'])) {
            throw new CM_Exception_Invalid('Invalid cm-bundler command: `' . $command . '`');
        }
        if (null === $options) {
            $options = [];
        }

        $config = $this->_getConfig($options);
        return CM_Util::exec('cm-bundler', [$command, json_encode($config)]);
    }

    /**
     * @param string    $name     require() module name
     * @param string    $content  inline script
     * @param bool|null $loadOnly true to execute the inline script, not executed by default
     * @param bool|null $expose   make the module available with require()
     */
    public function addInlineContent($name, $content, $loadOnly = null, $expose = null) {
        $this->_content[] = [
            'path'    => $name,
            'source'  => $content,
            'execute' => !((bool) $loadOnly),
            'expose'  => (bool) $expose
        ];
    }

    /**
     * @param string $entryPath include all require() recursively + execute the entry code
     */
    public function addEntryPath($entryPath) {
        $this->_entryPath[] = $entryPath;
    }

    /**
     * @param array $entryPaths
     */
    public function addEntryPaths($entryPaths) {
        $this->_entryPath = array_merge($this->_entryPath, $entryPaths);
    }

    /**
     * @param string $sourcePath use those paths to resolve require() calls
     */
    public function addSourcePath($sourcePath) {
        $this->_sourcePath[] = $sourcePath;
    }

    /**
     * @param array $sourcePaths
     */
    public function addSourcePaths($sourcePaths) {
        $this->_sourcePath = array_merge($this->_sourcePath, $sourcePaths);
    }

    /**
     * @param string $rawPath concatenated source code
     */
    public function addRawPath($rawPath) {
        $this->_rawPath[] = $rawPath;
    }

    /**
     * @param array $rawPaths
     */
    public function addRawPaths($rawPaths) {
        $this->_rawPath = array_merge($this->_rawPath, $rawPaths);
    }

    /**
     * @param string $libraryPath exposed library path (available in the global scope with require())
     */
    public function addLibraryPath($libraryPath) {
        $this->_libraryPath[] = $libraryPath;
    }

    /**
     * @param array $libraryPaths
     */
    public function addLibraryPaths($libraryPaths) {
        $this->_libraryPath = array_merge($this->_libraryPath, $libraryPaths);
    }

    /**
     * @param array $mapping
     */
    public function addSourceMapping($mapping) {
        $this->_sourceMapping = array_merge($this->_sourceMapping, $mapping);
    }

    /**
     * @return array
     */
    public function getSourceMapping() {
        return $this->_sourceMapping;
    }

    /**
     * @param array|null $extra
     * @return array
     */
    protected function _getConfig(array $extra = null) {
        if (null === $extra) {
            $extra = [];
        }
        return array_merge([
            'entries'   => $this->_entryPath,
            'libraries' => $this->_libraryPath,
            'content'   => $this->_content,
            'concat'    => $this->_rawPath,
            'paths'     => $this->_sourcePath,
            'baseDir'   => '/'
        ], $extra);
    }

    /**
     * @return string
     */
    protected function _getContent() {
        return $this->_getInlineContent() . $this->_getFileContent();
    }

    /**
     * @return string
     */
    protected function _getFileContent() {
        $paths = array_merge($this->_sourcePath, $this->_entryPath);
        return \Functional\reduce_left($paths, function ($path, $index, $collection, $carry) {
            return $carry . \Functional\reduce_left(CM_Util::rglob('*.js', $path), function ($filePath, $index, $collection, $carry) {
                return $carry . md5((new CM_File($filePath))->read());
            }, '');
        }, '');
    }

    /**
     * @return string
     */
    protected function _getInlineContent() {
        return \Functional\reduce_left($this->_content, function ($content, $index, $collection, $carry) {
            return $carry . $content['name'] . $content['data'] . $content['require'];
        }, '');
    }
}
