<?php

namespace Ripcord\Server;

use Ripcord\Exceptions\BadMethodCallException;
use Ripcord\Exceptions\ConfigurationException;
use Ripcord\Exceptions\InvalidArgumentException;
use Ripcord\Ripcord;

class Server
{
    /**
     * Contains a reference to the Ripcord documentor object.
     *
     * @see Ripcord_Documentor
     */
    private $documentor = null;

    /**
     * Contains a reference to the XML-RPC server created with xmlrpc_server_create.
     */
    private $xmlrpc = null;

    /**
     * Contains a list of methods set for this server. Excludes the system.* methods automatically
     * created by PHP's xmlrpc_server_create.
     */
    private $methods = [];

    /**
     * Contains an array with outputOptions, used when calling methods on the xmlrpc server created with
     * xmlrpc_server_create. These options can be overridden through the $options parameter of the
     * Ripcord_Server constructor.
     *
     * @see Ripcord_Server::setOutputOption()
     */
    private $outputOptions = [
        'output_type' => 'xml',
        'verbosity'   => 'pretty',
        'escaping'    => ['markup'],
        'version'     => 'auto',
        'encoding'    => 'utf-8',
    ];

    /**
     * Creates a new instance of the Ripcord server.
     *
     * @param mixed $services   . Optional. An object or array of objects.
     *                          The public methods in these objects will be exposed through the RPC server.
     *                          If the services array has non-numeric keys, the key for each object will define its namespace.
     * @param array $options    . Optional. Allows you to override the default server settings. Accepted key names are:
     *                          - 'documentor': allows you to specify an alternative HTML documentor class, no HTML documentor.
     *                          - 'name'      : The name of the server, used by the default HTML documentor.
     *                          - 'css'       : An url of a css file to link to in the HTML documentation.
     *                          - 'wsdl'      : The wsdl 1.0 description of this service ('soap 1.1' version, or the 'auto' version
     *                          - 'wsdl2'     : The wsdl 2.0 description of this service
     *                          In addition you can set any of the outputOptions for the xmlrpc server.
     * @param null  $documentor
     *
     * @throws ConfigurationException when the xmlrpc extension in not available.
     *
     * @see Ripcord_Server::setOutputOption()
     */
    public function __construct($services = null, $options = null, $documentor = null)
    {
        if (!function_exists('xmlrpc_server_create')) {
            throw new ConfigurationException(
                'PHP XMLRPC library is not installed',
                Ripcord::XMLRPC_NOT_INSTALLED
            );
        }
        $this->xmlrpc = xmlrpc_server_create();
        if (isset($services)) {
            if (is_array($services)) {
                foreach ($services as $serviceName => $service) {
                    $this->addService($service, $serviceName);
                }
            } else {
                $this->addService($services);
            }
        }
        if (isset($documentor) && is_object($documentor)) {
            $this->documentor = $documentor;
            xmlrpc_server_register_introspection_callback(
                $this->xmlrpc,
                [$this->documentor, 'getIntrospectionXML']
            );
        }
        if (isset($options)) {
            $this->outputOptions = array_merge($this->outputOptions, $options);
        }
    }

    /**
     * Allows you to add a service to the server after construction.
     *
     * @param object     $service     The object or class whose public methods must be added to the rpc server.
     *                                May also be a function or method.
     * @param int|string $serviceName Optional. The namespace for the methods.
     */
    public function addService($service, $serviceName = 0)
    {
        if (is_object($service)) {
            $reflection = new \ReflectionObject($service);
        } elseif (is_string($service) && class_exists($service)) {
            $reflection = new \ReflectionClass($service);
        } elseif (is_callable($service)) {
            // method passed directly

            $this->addMethod($serviceName, $service);

            return;
        } else {
            throw new InvalidArgumentException(
                'Unknown service type '.$serviceName,
                Ripcord::UNKNOWN_SERVICE_TYPE
            );
        }
        if ($serviceName && !is_numeric($serviceName)) {
            $serviceName .= '.';
        } else {
            $serviceName = '';
        }
        $methods = $reflection->getMethods();
        if (is_array($methods)) {
            foreach ($methods as $method) {
                if (substr($method->name, 0, 1) != '_'
                    && !$method->isPrivate() && !$method->isProtected()) {
                    $rpcMethodName = $serviceName.$method->name;
                    $this->addMethod(
                        $rpcMethodName,
                        [$service, $method->name]
                    );
                }
            }
        }
    }

    /**
     * Allows you to add a single method to the server after construction.
     *
     * @param string   $name   The name of the method as exposed through the rpc server
     * @param callable $method The name of the method to call, or an array with classname or object and method name.
     */
    public function addMethod($name, $method)
    {
        $this->methods[$name] = [
            'name' => $name,
            'call' => $method,
        ];
        xmlrpc_server_register_method($this->xmlrpc, $name, [$this, 'call']);
    }

    /**
     * Runs the rpc server. Automatically handles an incoming request.
     */
    public function run()
    {
        if ($this->documentor) {
            $this->documentor->setMethodData($this->methods);
        }
        $request_xml = file_get_contents('php://input');
        if (!$request_xml) {
            if (($query = $_SERVER['QUERY_STRING'])
                && isset($this->wsdl[$query]) && $this->wsdl[$query]) {
                header('Content-type: text/xml');
                header('Access-Control-Allow-Origin: *');
                echo $this->wsdl[$query];
            } elseif ($this->documentor) {
                header('Content-type: text/html; charset='.$this->outputOptions['encoding']);
                $this->documentor->handle($this, $this->methods);
            } else {
                // FIXME: add check for json-rpc protocol, if set and none of the xml protocols are set, use that
                header('Content-type: text/xml');
                header('Access-Control-Allow-Origin: *');
                echo xmlrpc_encode_request(
                    null,
                    ripcord::fault(-1, 'No request xml found.'),
                    $this->outputOptions
                );
            }
        } else {
            // FIXME: add check for the protocol of the request, could be json-rpc, then check if it is supported.
            header('Content-type: text/xml');
            header('Access-Control-Allow-Origin: *');
            echo $this->handle($request_xml);
        }
    }

    /**
     * This method wraps around xmlrpc_decode_request, since it is broken in many ways. This wraps
     * around all the ugliness needed to make it not dump core and not print expat warnings.
     *
     * @param $request_xml
     *
     * @return array|string
     */
    private function parseRequest($request_xml)
    {
        $xml = simplexml_load_string($request_xml);
        if (!$xml && !$xml->getNamespaces()) {
            // FIXME: check for protocol json-rpc
            //simpl exml in combination with namespaces (soap) lets $xml evaluate to false
            return  xmlrpc_encode_request(
                null,
                Ripcord::fault(
                    -3,
                    'Invalid Method Call - Ripcord Server accepts only XML-RPC, SimpleRPC or SOAP 1.1 calls'
                ),
                $this->outputOptions
            );
        } else {
            // prevent segmentation fault on incorrect xmlrpc request (without methodName)
            $methodCall = $xml->xpath('//methodCall');
            if ($methodCall) { //xml-rpc
                $methodName = $xml->xpath('//methodName');
                if (!$methodName) {
                    return xmlrpc_encode_request(
                        null,
                        Ripcord::fault(-3, 'Invalid Method Call - No methodName given'),
                        $this->outputOptions
                    );
                }
            }
        }
        $method = null;
        ob_start(); // xmlrpc_decode echo expat errors if the xml is not valid, can't stop it.
        $params = xmlrpc_decode_request($request_xml, $method, 'utf-8');
        ob_end_clean(); // clean up any xml errors
        return ['methodName' => $method, 'params' => $params];
    }

    /**
     * This method implements the system.multiCall method without dumping core. The built-in method from the
     * xmlrpc library dumps core when you have registered any php methods, fixed in php 5.3.2.
     *
     * @param null $params
     *
     * @return array|string
     */
    private function multiCall($params = null)
    {
        if ($params && is_array($params)) {
            $result = [];
            $params = $params[0];
            foreach ($params as $param) {
                $method = $param['methodName'];
                $args = $param['params'];

                try {
                    // XML-RPC specification says that non-fault results must be in a single item array
                    $result[] = [$this->call($method, $args)];
                } catch (\Exception $e) {
                    $result[] = Ripcord::fault($e->getCode(), $e->getMessage());
                }
            }
            $result = xmlrpc_encode_request(null, $result, $this->outputOptions);
        } else {
            $result = xmlrpc_encode_request(
                null,
                Ripcord::fault(-2, 'Illegal or no params set for system.multiCall'),
                $this->outputOptions
            );
        }

        return $result;
    }

    /**
     * Handles the given request xml.
     *
     * @param string $request_xml The incoming request.
     *
     * @return string
     */
    public function handle($request_xml)
    {
        $result = $this->parseRequest($request_xml);
        if (!$result || Ripcord::isFault($result)) {
            return $result;
        } else {
            $method = $result['methodName'];
            $params = $result['params'];
        }
        if ($method == 'system.multiCall' || $method == 'system.multicall') {
            // php's xml-rpc server (xmlrpc-epi) crashes on multi call, so handle it ourselves... fixed in php 5.3.2
            $result = $this->multiCall($params);
        } else {
            try {
                $result = xmlrpc_server_call_method($this->xmlrpc, $request_xml, null, $this->outputOptions);
            } catch (\Exception $e) {
                $result = xmlrpc_encode_request(
                    null,
                    Ripcord::fault($e->getCode(), $e->getMessage()),
                    $this->outputOptions
                );
            }
        }

        return $result;
    }

    /**
     * Calls a method by its rpc name.
     *
     * @param string $method The rpc name of the method
     * @param array  $args   The arguments to this method
     *
     * @throws InvalidArgumentException (ripcord::cannotRecurse) when passed a recursive multiCall
     * @throws BadMethodCallException   (ripcord::methodNotFound) when the requested method isn't available.
     *
     * @return mixed
     */
    public function call($method, $args = null)
    {
        if ($this->methods[$method]) {
            $call = $this->methods[$method]['call'];

            return call_user_func_array($call, $args);
        } else {
            if (substr($method, 0, 7) == 'system.') {
                if ($method == 'system.multiCall') {
                    throw new InvalidArgumentException(
                        'Cannot recurse system.multiCall',
                        Ripcord::CANNOT_RECURSE
                    );
                }
                // system methods are handled internally by the xmlrpc server,
                // so we've got to create a make believe request,
                // there is no other way because of a badly designed API
                $req = xmlrpc_encode_request($method, $args, $this->outputOptions);
                $result = xmlrpc_server_call_method($this->xmlrpc, $req, null, $this->outputOptions);

                return xmlrpc_decode($result, 'utf-8');
            } else {
                throw new BadMethodCallException(
                    'Method '.$method.' not found.',
                    Ripcord::METHOD_NOT_FOUND
                );
            }
        }
    }

    /**
     * Allows you to set specific output options of the server after construction.
     *
     * @param string $option The name of the option
     * @param mixed  $value  The value of the option
     *                       The options are:
     *                       - output_type: Return data as either php native data or xml encoded.
     *                       Can be either 'php' or 'xml'. 'xml' is the default.
     *                       - verbosity: Determines the compactness of generated xml.
     *                       Can be either 'no_white_space', 'newlines_only' or 'pretty'.
     *                       'pretty' is the default.
     *                       - escaping: Determines how/whether to escape certain characters. 1 or more values are allowed.
     *                       If multiple, they need to be specified as a sub-array.
     *                       Options are: 'cdata', 'non-ascii', 'non-print' and 'markup'. Default is 'non-ascii', 'non-print' and 'markup'.
     *                       - version: Version of the xml vocabulary to use.
     *                       Currently, three are supported: 'xmlrpc', 'soap 1.1' and 'simple'.
     *                       The keyword 'auto' is also recognized and tells the server to respond in whichever version
     *                       the request cam in. 'auto' is the default.
     *                       - encoding: The character encoding that the data is in.
     *                       Can be any supported character encoding. Default is 'utf-8'.
     *
     * @return bool
     */
    public function setOutputOption($option, $value)
    {
        if (isset($this->outputOptions[$option])) {
            $this->outputOptions[$option] = $value;

            return true;
        } else {
            return false;
        }
    }
}
