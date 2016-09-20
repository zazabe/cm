<?php

class CM_Log_Handler_Factory implements CM_Service_ManagerAwareInterface {

    use CM_Service_ManagerAwareTrait;

    /**
     * @param array[] $layersConfig
     * @return CM_Log_Handler_Layered
     * @throws CM_Exception_Invalid
     */
    public function createLayeredHandler($layersConfig) {
        $layeredHandler = new CM_Log_Handler_Layered();
        foreach ($layersConfig as $layerConfig) {
            $layer = new CM_Log_Handler_Layered_Layer();
            foreach ($layerConfig as $handlerServiceName) {
                $layer->addHandler($this->getServiceManager()->get($handlerServiceName, 'CM_Log_Handler_HandlerInterface'));
            }
            $layeredHandler->addLayer($layer);
        }
        return $layeredHandler;
    }

    /**
     * @param string   $hostname
     * @param int      $port
     * @param string   $tag
     * @param int|null $minLevel
     * @return CM_Log_Handler_Fluentd
     */
    public function createFluentdLogger($hostname, $port, $tag, $minLevel = null) {
        $fluentd = new \Fluent\Logger\FluentLogger($hostname, $port);
        $appName = CM_App::getInstance()->getName();
        $contextFormatter = new CM_Log_ContextFormatter_Cargomedia($appName);
        $formatter = new CM_Log_Formatter_Array($contextFormatter);
        return new CM_Log_Handler_Fluentd($formatter, $fluentd, $tag, $minLevel);
    }

    /**
     * @param string   $collection
     * @param int|null $recordTtl Time To Live in seconds
     * @param array    $insertOptions
     * @param int|null $minLevel
     * @return CM_Log_Handler_MongoDb
     */
    public function createMongoDbLogger($collection, $recordTtl = null, array $insertOptions = null, $minLevel = null) {
        $mongoDb = $this->getServiceManager()->getMongoDb();
        $contextFormatter = new CM_Log_ContextFormatter_Array();
        $formatter = new CM_Log_Formatter_Array($contextFormatter);
        return new CM_Log_Handler_MongoDb($formatter, $mongoDb, $collection, $recordTtl, $insertOptions, $minLevel);
    }

    /**
     * @param int|null $minLevel
     * @return CM_Log_Handler_NewRelic
     */
    public function createNewRelicLogger($minLevel = null) {
        $newRelic = $this->getServiceManager()->getNewrelic();
        $formatter = new CM_Log_Formatter_Raw();
        return new CM_Log_Handler_NewRelic($formatter, $newRelic, $minLevel);
    }

    /**
     * @param string|null $formatMessage
     * @param string|null $formatDate
     * @param int|null    $minLevel
     * @return CM_Log_Handler_Stream
     */
    public function createStderrHandler($formatMessage = null, $formatDate = null, $minLevel = null) {
        $formatMessage = null !== $formatMessage ? (string) $formatMessage : $formatMessage;
        $formatDate = null !== $formatDate ? (string) $formatDate : $formatDate;

        $stream = new CM_OutputStream_Stream_StandardError();
        $formatter = new CM_Log_Formatter_Text($formatMessage, $formatDate);
        return $this->_createStreamHandler($stream, $formatter, $minLevel);
    }

    /**
     * @param string      $path
     * @param string|null $formatMessage
     * @param string|null $formatDate
     * @param int|null    $minLevel
     * @return CM_Log_Handler_Stream
     */
    public function createFileHandler($path, $formatMessage = null, $formatDate = null, $minLevel = null) {
        $path = (string) $path;
        $formatMessage = null !== $formatMessage ? (string) $formatMessage : $formatMessage;
        $formatDate = null !== $formatDate ? (string) $formatDate : $formatDate;

        $filesystem = $this->getServiceManager()->getFilesystems()->getData();
        $file = new CM_File($path, $filesystem);
        $file->ensureParentDirectory();
        $stream = new CM_OutputStream_File($file);
        $formatter = new CM_Log_Formatter_Text($formatMessage, $formatDate);
        return $this->_createStreamHandler($stream, $formatter, $minLevel);
    }

    /**
     * @param CM_OutputStream_Interface $stream
     * @param CM_Log_Formatter_Abstract $formatter
     * @param int|null                  $minLevel
     * @return CM_Log_Handler_Stream
     */
    protected function _createStreamHandler(CM_OutputStream_Interface $stream, CM_Log_Formatter_Abstract $formatter, $minLevel = null) {
        $minLevel = null !== $minLevel ? (int) $minLevel : $minLevel;
        return new CM_Log_Handler_Stream($formatter, $stream, $minLevel);
    }
}
