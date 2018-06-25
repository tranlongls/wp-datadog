<?php

class Span
{
    protected $id;
    protected $name;
	protected $start;
	protected $resource;
    protected $tags;
	
	public function __construct(array $config = array())
    {
        $this->id = $this->nextId();
        $this->start = isset($config['start']) ? (int)$config['start'] : $this->now();
        $this->name = isset($config['name']) ? $config['name'] : "web.request";
        $this->resource = isset($config['resource']) ? $config['resource'] : $_SERVER['REQUEST_URI'];
        $this->tags = [];
    }

    public function now()
    {   
        return (int)(microtime(true) * 1000 * 1000);
    }

    public function nextId()
    {
        return bin2hex(openssl_random_pseudo_bytes(8));
    }

    /**
     * @param Span $span
     * @return string
     */
    public function export()
    {
        $result = [
            'id' => $this->id,
            'name' => $this->name,
            'resource' => $this->resource,
            'service' => $this->service,
            'start' => $this->start,
            'error' => 0,
            'type' => 'web',
            'duration' => $this->duration,
            'parent_id' => 0
        ];
        if(count($this->tags)){
            $result['meta'] = $this->tags;
        }
        return $result;
    }

    public function finish($finishTime = null){
        if(!$finishTime){
            $finishTime = $this->now();
        }
        $this->duration = $finishTime - $this->start;
    }

    public function setMeta($key, $value){
        $this->tags[$key] = (string)$value;
    }

    public function setHttpObj($httpObj){
        foreach ($httpObj as $key => $value) {
            $this->setMeta("http.".$key, $value);
        }
    }
}
