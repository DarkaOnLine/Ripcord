<?php

namespace Ripcord\Documentator;

use Ripcord\Documentator\Contracts\Documentator as DocumentorContract;

/**
 * This class implements the default documentor for the ripcord server. Any request to the server
 * without a request_xml is handled by the documentor.
 */
class Documentor implements DocumentorContract
{
    /**
     * The object to parse the docComments.
     */
    private $docCommentParser = null;

    /**
     * The name of the rpc server, used as the title and heading of the default HTML page.
     */
    public $name = 'Ripcord: Simple RPC Server';

    /**
     * A url to an optional css file or a css string for an inline stylesheet.
     */
    public $css = "
		html {
			font-family: georgia, times, serif;
			font-size: 79%;
			background-color: #EEEEEE;
		}
		h1 {
			font-family: 'arial black', helvetica, sans-serif;
			font-size: 2em;
			font-weight: normal;
			margin: -20px -21px 0.4em -20px;
			padding: 40px 20px 20px;
			background: #01648E; /* for non-css3 browsers */
			filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#00486E', endColorstr='#09799E'); /* for IE */
			background: -webkit-gradient(linear, left top, left bottom, from(#00486E), to(#09799E)); /* for webkit browsers */
			background: -moz-linear-gradient(top,  #00486E,  #09799E); /* for firefox 3.6+ */
			color: white;
			border-bottom: 4px solid black;
			text-shadow: black 0.1em 0.1em 0.2em;
		}
		h2 {
			font-family: arial, helvetica, sans-serif;
			font-weight: bold;
			font-size: 1.4em;
			color: #444444;
			text-shadow: #AAAAAA 0.1em 0.1em 0.2em;
			margin-top: 2.5em;
			border-bottom: 1px solid #09799E;
		}
		h3 {
			font-family: arial, helvetica, sans-serif;
			font-weight: normal;
			font-size: 1.4em;
			color: #555555;
			text-shadow: #AAAAAA 0.1em 0.1em 0.2em;
			margin-bottom: 0px;
		}
		div.signature {
			font-family: courier, monospace;
			margin-bottom: 1.4em;
		}
		ul, ol, li {
			margin: 0px;
			padding: 0px;
		}
		ul, ol {
			color: #09799E;
			margin-bottom: 1.4em;
		}
		ul li {
			list-style: square;
		}
		ul li, ol li {
			margin-left: 20px;
		}
		li span, li label {
			color: black;
		}
		li.param label {
			font-family: courier, monospace;
			padding-right: 1.4em;
		}
		a {
			text-decoration: none;
		}
		a:hover {
			text-decoration: underline;
		}
		body {
			background-color: white;
			width: 830px;
			margin: 10px auto;
			padding: 20px;
			-moz-box-shadow: 5px 5px 5px #ccc;
			-webkit-box-shadow: 5px 5px 5px #ccc;
			box-shadow: 5px 5px 5px #ccc;
		}
		code {
			display: block;
			background-color: #999999;
			padding: 10px;
			margin: 0.4em 0px 1.4em 0px;
			color: white;
			white-space: pre;
			font-family: courier, monospace;
			font-size: 1.2em;
		}
		.tag, .argName, .argType {
			margin-right: 10px;
		}
		.argument {
			margin-left: 20px;
		}
		.footer {
			font-family: helvetica, sans-serif;
			font-size: 0.9em;
			font-weight: normal;
			margin: 0px -21px -20px -20px;
			padding: 20px;
			background: #01648E; /* for non-css3 browsers */
			filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#00486E', endColorstr='#09799E'); /* for IE */
			background: -webkit-gradient(linear, left top, left bottom, from(#00486E), to(#09799E)); /* for webkit browsers */
			background: -moz-linear-gradient(top,  #00486E,  #09799E); /* for firefox 3.6+ */
			color: white;
		}
		.footer a {
			color: white;
			text-decoration: none;
		}
	";

    /**
     * The wsdl 1.0 description.
     */
    public $wsdl = false;

    /**
     * The wsdl 2.0 description.
     */
    public $wsdl2 = false;

    /**
     * Which version of the XML vocabulary the server implements. Either 'xmlrpc', 'soap 1.1', 'simple' or 'auto'.
     */
    public $version = 'auto';

    /**
     * The root URL of the rpc server.
     */
    public $root = '';

    /**
     * Optional header text for the online documentation.
     */
    public $header = '';

    /**
     * Optional footer text for the online documentation.
     */
    public $footer = '';

    /**
     * A list of method data, containing all the user supplied methods the rpc server implements.
     */
    private $methods = null;

    /**
     * The constructor for the Ripcord_Documentor class.
     *
     * @param array $options          . Optional. Allows you to set the public properties of this class upon construction.
     * @param null  $docCommentParser
     */
    public function __construct($options = null, $docCommentParser = null)
    {
        $check = ['name', 'css', 'wsdl', 'wsdl2', 'root', 'version', 'header', 'footer'];
        foreach ($check as $name) {
            if (isset($options[$name])) {
                $this->{$name} = $options[$name];
            }
        }
        $this->docCommentParser = $docCommentParser;
    }

    /**
     * This method fills the list of method data with all the user supplied methods of the rpc server.
     *
     * @param array $methodData A list of methods with name and callback information.
     */
    public function setMethodData($methodData)
    {
        $this->methods = $methodData;
    }

    /**
     * This method handles any request which isn't a valid rpc request.
     *
     * @param object $rpcServer A reference to the active rpc server.
     */
    public function handle($rpcServer)
    {
        $methods = $rpcServer->call('system.listMethods');
        echo '<!DOCTYPE html>';
        echo '<html><head><title>'.$this->name.'</title>';
        if (isset($this->css)) {
            if (strpos($this->css, "\n") !== false) {
                echo '<style type="text/css">'.$this->css.'</style>';
            } else {
                echo '<link rel="stylesheet" type="text/css" href="'.$this->css.'">';
            }
        }
        echo '</head><body>';
        echo '<div class="content">';
        echo '<h1>'.$this->name.'</h1>';
        echo $this->header;
        echo '<p>';
        $showWSDL = false;
        switch ($this->version) {
            case 'xmlrpc':
                echo 'This server implements the <a href="http://www.xmlrpc.com/spec">XML-RPC specification</a>';
                break;
            case 'simple':
                echo 'This server implements the <a href="http://sites.google.com/a/simplerpc.org/simplerpc/Home/simplerpc-specification-v09">SimpleRPC 1.0 specification</a>';
                break;
            case 'auto':
                echo 'This server implements the <a href="http://www.w3.org/TR/2000/NOTE-SOAP-20000508/">SOAP 1.1</a>, <a href="http://www.xmlrpc.com/spec">XML-RPC</a> and <a href="http://sites.google.com/a/simplerpc.org/simplerpc/Home/simplerpc-specification-v09">SimpleRPC 1.0</a> specification.';
                $showWSDL = true;
                break;
            case 'soap 1.1':
                echo 'This server implements the <a href="http://www.w3.org/TR/2000/NOTE-SOAP-20000508/">SOAP 1.1 specification</a>.';
                $showWSDL = true;
                break;
        }
        echo '</p>';
        if ($showWSDL && ($this->wsdl || $this->wsdl2)) {
            echo '<ul>';
            if ($this->wsdl) {
                echo '<li><a href="'.$this->root.'?wsdl">WSDL 1.1 Description</a></li>';
            }
            if ($this->wsdl2) {
                echo '<li><a href="'.$this->root.'?wsdl2">WSDL 2.0 Description</a></li>';
            }
            echo '</ul>';
        }

        $methods = $rpcServer->call('system.describeMethods');
        $allMethods = [];
        $allFunctions = [];
        foreach ($methods['methodList'] as $index => $method) {
            if (strpos($method, '.') !== false) {
                $allMethods[$method['name']] = $index;
            } else {
                $allFunctions[$method['name']] = $index;
            }
        }
        ksort($allMethods);
        ksort($allFunctions);
        $allMethods = $allFunctions + $allMethods;

        echo '<div class="index"><h2>Methods</h2><ul>';
        foreach ($allMethods as $methodName => $methodIndex) {
            echo '<li><a href="#method_'.(int) $methodIndex.'">'.$methodName.'</a></li>';
        }
        echo '</ul></div>';

        $currentClass = '';
        echo '<div class="functions">';
        foreach ($allMethods as $methodName => $methodIndex) {
            $method = $methods['methodList'][$methodIndex];
            $pos = strpos($methodName, '.');
            if ($pos !== false) {
                $class = substr($methodName, 0, $pos);
            }
            if ($currentClass != $class) {
                echo '</div>';
                echo '<div class="class_'.$class.'">';
                $currentClass = $class;
            }
            echo '<h2 id="method_'.$methodIndex.'">'.$method['name'].'</h2>';
            if ($method['signatures']) {
                foreach ($method['signatures'] as $signature) {
                    echo '<div class="signature">';
                    if (is_array($signature['returns'])) {
                        $return = $signature['returns'][0];
                        echo '('.$return['type'].') ';
                    }
                    echo $method['name'].'(';
                    $paramInfo = false;
                    if (is_array($signature['params'])) {
                        $paramInfo = $signature['params'];
                        $params = '';
                        foreach ($signature['params'] as $param) {
                            $params .= ', ('.$param['type'].') '.$param['name'].' ';
                        }
                        echo substr($params, 1);
                    }
                    echo ')</div>';
                    if (is_array($paramInfo)) {
                        echo '<div class="params"><h3>Parameters</h3><ul>';
                        foreach ($paramInfo as $param) {
                            echo '<li class="param">';
                            echo '<label>('.$param['type'].') '.$param['name'].'</label> ';
                            echo '<span>'.$param['description'].'</span>';
                            echo '</li>';
                        }
                        echo '</ul></div>';
                    }
                }
            }

            if ($method['purpose']) {
                echo '<div class="purpose">'.$method['purpose'].'</div>';
            }

            if (is_array($method['notes'])) {
                echo '<div class="notes"><h3>Notes</h3><ol>';
                foreach ($method['notes'] as $note) {
                    echo '<li><span>'.$note.'</span></li>';
                }
                echo '</ol></div>';
            }

            if (is_array($method['see'])) {
                echo '<div class="see">';
                echo '<h3>See</h3>';
                echo '<ul>';
                foreach ($method['see'] as $link => $description) {
                    echo '<li>';
                    if (isset($allMethods[$link])) {
                        echo '<a href="#method_'.(int) $allMethods[$link].'">'.$link.'</a> <span>'.$description.'</span>';
                    } else {
                        echo '<span>'.$link.' '.$description.'</span>';
                    }
                    echo '</li>';
                }
                echo '</ul></div>';
            }
        }
        echo '</div>';
        echo $this->footer;
        echo '<div class="footer">';
        echo 'Powered by <a href="http://ripcord.googlecode.com/">Ripcord : Simple RPC Server</a>.';
        echo '</div>';
        echo '</div></body></html>';
    }

    /**
     * This method returns an XML document in the introspection format expected by
     * xmlrpc_server_register_introspection_callback. It uses the php Reflection
     * classes to gather information from the registered methods.
     * Descriptions are added from phpdoc doc blocks if found.
     *
     * @return string XML string with the introspection data.
     */
    public function getIntrospectionXML()
    {
        $xml = "<?xml version='1.0' ?><introspection version='1.0'><methodList>";
        if (isset($this->methods) && is_array($this->methods)) {
            foreach ($this->methods as $method => $methodData) {
                if (is_array($methodData['call'])) {
                    $reflection = new \ReflectionMethod(
                        $methodData['call'][0],
                        $methodData['call'][1]
                    );
                } else {
                    $reflection = new \ReflectionFunction($methodData['call']);
                }
                $description = $reflection->getDocComment();
                if ($description && $this->docCommentParser) {
                    $data = $this->docCommentParser->parse($description);
                    if ($data['description']) {
                        $description = $data['description'];
                    }
                }
                if ($description) {
                    $description = '<p>'.str_replace(["\r\n\r\n", "\n\n"], '</p><p>', $description)
                        .'</p>';
                }
                if (is_array($data)) {
                    foreach ($data as $key => $value) {
                        switch ($key) {
                            case 'category':
                            case 'deprecated':
                            case 'package':
                                $description .= '<div class="'.$key.'"><span class="tag">'
                                    .$key.'</span>'.$value.'</div>';
                                break;

                            default:
                                break;
                        }
                    }
                }
                $xml .= '<methodDescription name="'.$method.'"><purpose><![CDATA['
                    .$description.']]></purpose>';
                if (is_array($data) && ($data['arguments'] || $data['return'])) {
                    $xml .= '<signatures><signature>';
                    if (is_array($data['arguments'])) {
                        $xml .= '<params>';
                        foreach ($data['arguments'] as $name => $argument) {
                            if ($name[0] == '$') {
                                $name = substr($name, 1);
                            }
                            $xml .= '<value type="'.htmlspecialchars($argument['type'])
                                .'" name="'.htmlspecialchars($name).'"><![CDATA['.$argument['description']
                                .']]></value>';
                        }
                        $xml .= '</params>';
                    }
                    if (is_array($data['return'])) {
                        $xml .= '<returns><value type="'.htmlspecialchars($data['return']['type'])
                            .'"><![CDATA['.$data['return']['description'].']]></value></returns>';
                    }
                    $xml .= '</signature></signatures>';
                }
                $xml .= '</methodDescription>';
            }
        }
        $xml .= '</methodList></introspection>';

        return $xml;
    }
}
