<?php
namespace Vmwarephp\Factory;
use \Vmwarephp\Exception as Ex;

class SoapClient {
	private $wsdlClassMapper;
	private $wsdlFilePath;

	function __construct(\Vmwarephp\WsdlClassMapper $mapper = null, $wsdlFilePath = null) {
		$this->wsdlClassMapper = $mapper ? : new \Vmwarephp\WsdlClassMapper;
		$this->wsdlFilePath = $wsdlFilePath ? : $this->getWsdlFilePath();
	}

	function make(\Vmwarephp\Vhost $vhost, $useExceptions = 1, $trace = 1) {
        $stream_context = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        if(isset($vhost->options['proxy_username']) && isset($vhost->options['proxy_pass'])){
            $stream_context['http'] = [
                'header' => "Authorization: Basic ". base64_encode($vhost->options['proxy_username'].":".$vhost->options['proxy_pass'])
            ];
        }

		$options = array(
			'trace' => $trace,
			'location' => $this->makeRequestsLocation($vhost),
			'exceptions' => $useExceptions,
			'connection_timeout' => 10,
			'classmap' => $this->wsdlClassMapper->getClassMap(),
			'features' => SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE,
            'stream_context' => stream_context_create($stream_context)
		);
		$soapClient = $this->makeDefaultSoapClient($this->wsdlFilePath, $options);
		if (!$soapClient) throw new Ex\CannotCreateSoapClient();
		return $soapClient;
	}

	function getClientClassMap() {
		return $this->wsdlClassMapper->getClassMap();
	}

	protected function makeRequestsLocation(\Vmwarephp\Vhost $vhost) {
		return 'https://' . $vhost->host . '/sdk';
	}

	protected function makeDefaultSoapClient($wsdl, array $options) {
		return @new \Vmwarephp\SoapClient($wsdl, $options);
	}

	private function getWsdlFilePath() {
		return __DIR__ . '/../Wsdl/vimService.wsdl';
	}
}
