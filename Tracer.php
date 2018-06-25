<?php

class Tracer
{
	protected $host;
	protected $port;
	public $id;
	protected $serviceName;
	protected $spans;
	public function __construct(array $config = array())
    {
    	$this->id = $this->nextId();
        $this->host = isset($config['host']) ? $config['host'] : 'localhost';
        $this->port = isset($config['port']) ? $config['port'] : 8126;
        $this->serviceName = isset($config['service']) ? $config['service'] : "grab";
        $this->spans = [];
    }

    public function nextId()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }
    /**
     * @param string $hex
     * @return string
     */
    private function hex2dec($hex)
    {
        return base_convert($hex, 16, 10);
    }

    public function flush($span){
    	$this->spans[] = $span;
    }

    public function export()
    {
    	$traces = [$this->spans];
        return '[' . implode(',', array_map(function ($trace) {
            return '[' . implode(',', array_map(function ($span) {
                return $this->encodeSpan($span);
            }, $trace)) . ']';
        }, $traces))  . ']';
    }
    /**
     * @param Span $span
     * @return array
     */
    private function spanToArray($span)
    {
        $arraySpan = [
            'trace_id_hex' => '-',
            'span_id_hex' => '-',
            'name' => $span['name'],
            'resource' => $span['resource'],
            'service' => $this->serviceName,
            'start_micro' => '-',
            'error' => 0,
            'type' => 'web',
            'duration_micro' => '-',
            'parent_id_hex' => '-'
        ];
        if($span['meta']){
            $arraySpan['meta'] = $span['meta'];
        }
        return $arraySpan;
    }
    /**
     * @param Span $span
     * @return string
     */
    private function encodeSpan($span)
    {
        return str_replace([
            '"start_micro":"-"',
            '"duration_micro":"-"',
            '"trace_id_hex":"-"',
            '"span_id_hex":"-"',
            '"parent_id_hex":"-"',
        ], [
            '"start":' . $span['start'] . '000',
            '"duration":' . $span['duration'] . '000',
            '"trace_id":' . $this->hex2dec($this->id),
            '"span_id":' . $this->hex2dec($span['id']),
            '"parent_id":' . $this->hex2dec(0),
        ], json_encode($this->spanToArray($span)));
    }
    /**
     * {@inheritdoc}
     */
    public function getContentType()
    {
        return 'application/json';
    }

    public function sendRequest($headers = [])
    {
    	$body = $this->export(); 
    	$host = $this->host;
    	$port = $this->port;
    	$url = "$host:$port/v0.3/traces";
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_POST, 1);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);  
        curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        curl_setopt($handle, CURLOPT_HTTPHEADER, array_merge($headers, [
            'Content-Type: ' . $this->getContentType(),
            'Content-Length: ' . strlen($body),
        ]));

        if (curl_exec($handle) !== true) {
            sprintf(
                'Reporting of spans failed: %s, error code %s',
                curl_error($handle),
                curl_errno($handle)
            );

            return;
        }

        $statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($statusCode === 415) {
            echo('Reporting of spans failed, upgrade your client library.');
            return;
        }

        if ($statusCode !== 200) {
            sprintf('Reporting of spans failed, status code %d', $statusCode);
            return;
        }
    }
}
