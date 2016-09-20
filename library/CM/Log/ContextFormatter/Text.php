<?php

class CM_Log_ContextFormatter_Text extends CM_Log_ContextFormatter_Raw {

    public function format(CM_Log_Context $context) {
        $user = $context->getUser();
        $httpRequest = $context->getHttpRequest();
        $extra = $context->getExtra();

        $data = [];

        if (null !== $user) {
            $data['user'] = $this->_format('id: {id}, email: {email}', [
                'id'    => $user->getId(),
                'email' => $user->getEmail(),
            ]);
        }
        if (null != $httpRequest) {
            $server = $httpRequest->getServer();
            $httpRequestText = '{type} {path} {proto}, host: {host}, ip: {ip}, referer: {referer}, user-agent: {agent}';
            $data['httpRequest'] = $this->_format($httpRequestText, [
                'type'    => isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : '',
                'path'    => $httpRequest->getPath(),
                'proto'   => isset($server['SERVER_PROTOCOL']) ? $server['SERVER_PROTOCOL'] : '',
                'host'    => $httpRequest->getHost(),
                'ip'      => $httpRequest->getIp(),
                'referer' => $httpRequest->hasHeader('referer') ? $httpRequest->getHeader('referer') : '',
                'agent'   => $httpRequest->getUserAgent(),
            ]);
        }
        if (!empty($extra)) {
            $data['extra'] = json_encode($extra, JSON_PRETTY_PRINT);
        }
        if ($exception = $context->getException()) {
            $serializableException = new CM_ExceptionHandling_SerializableException($exception);
            $data['exception'] = $this->_renderException($serializableException);
        }
        $output = empty($data) ? null : $this->_formatArrayToLines(' - %s: %s', $data);
        return $output;
    }

    /**
     * @param string $format
     * @param array  $data
     * @return string
     */
    protected function _formatArrayToLines($format, array $data) {
        $format = (string) $format;
        $dataText = [];
        foreach ($data as $key => $value) {
            $dataText[] = sprintf($format, $key, $value);
        }
        return implode(PHP_EOL, $dataText);
    }

    /**
     * @param CM_ExceptionHandling_SerializableException $exception
     * @return string
     */
    protected function _renderException(CM_ExceptionHandling_SerializableException $exception) {
        $traceData = [];
        $traceCount = 0;
        foreach ($exception->getTrace() as $trace) {
            $traceData[] = sprintf('     %02d. %s %s:%s', $traceCount++, $trace['code'], $trace['file'], $trace['line']);
        }
        $traceText = implode(PHP_EOL, $traceData);

        return PHP_EOL . $this->_formatArrayToLines('   - %s: %s', [
            'message'    => $exception->getMessage(),
            'type'       => $exception->getClass(),
            'stacktrace' => PHP_EOL . $traceText,
        ]);
    }

    /**
     * @param string $text
     * @param array  $data
     * @return string
     */
    protected function _format($text, array $data) {
        $text = (string) $text;
        return preg_replace_callback('/\{([a-z]+)\}/i', function ($matches) use ($data) {
            return isset($data[$matches[1]]) ? $data[$matches[1]] : '';
        }, $text);
    }
}
