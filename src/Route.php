<?php

namespace PhpApi;

/**
 * Model of a Route, informs class and its method that will be triggered
 * when a pattern is found (works with specific HTTP methods if needed)
 * 
 * @package PhpApi
 * @copyright (c) 2017, Federal Institute of Rondonia
 * @license http://gnu.org/licenses/lgpl.txt LGPL-3.0+
 * @author Natanael Simoes <natanael.simoes@ifro.edu.br>
 * @since Release 0.1.0
 * @link https://github.com/ifroariquemes/PHP-API Github repository
 */
class Route
{

    /**
     * The route pattern that trigger the class method
     * @var string 
     */
    private $pattern;

    /**
     * The class of route method
     * @var string
     */
    private $class;

    /**
     * The method that will be executed
     * @var string
     */
    private $method;

    /**
     * Array of HTTP methods used to trigger. Can be GET, POST, PUT and others
     * @var array
     */
    private $httpMethods = array();

    /**
     * Instantiate a object of Route
     * @param string $pattern The route pattern that trigger the class method
     * @param string $class The class of route method
     * @param string $method The method that will be executed
     * @param string $httpMethod (optional) null to process any HTTP method, string to specify which one (separated by commas)
     */
    public function __construct(string $pattern, string $class, string $method, string $httpMethod = null)
    {
        $this->pattern = $pattern;
        $this->class = $class;
        $this->method = $method;
        $arHM = explode(',', $httpMethod);
        foreach ($arHM as $httpMethod) {
            array_push($this->httpMethods, trim($httpMethod));
        }
    }

    /**
     * Returns the pattern string
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * Checks if the route accepts the pattern
     * from the request 
     * @param string $pattern The pattern
     * @return bool
     */
    public function acceptPattern(string $pattern): bool
    {
        return ($this->pattern === $pattern);
    }

    /**
     * Checks if the route accepts the HTTP request method
     * @param string $httpMethod The method
     * @return bool
     */
    public function acceptHttpMethod(string $httpMethod = null): bool
    {
        return is_null($httpMethod) || in_array($httpMethod, $this->httpMethods);
    }

    /**
     * Returns a Reflection Method object
     * @return \ReflectionMethod
     */
    public function getReflectionMethod(): \ReflectionMethod
    {
        return new \ReflectionMethod($this->class, $this->method);
    }

    /**
     * Returns all the methods params
     * @return array
     */
    public function getMethodParams(): array
    {
        $refParams = array();
        foreach ($this->getReflectionMethod()->getParameters() as $param) {
            array_push($refParams, $param->getName());
        }
        return $refParams;
    }

    /**
     * Executes the class method and its parameters (if needed)
     * @param array $params Method parameters
     */
    public function execute(array $params = null)
    {
        $strParams = ' ';
        if (!empty($params)) {
            foreach ($params as $param) {
                $strParams .= "$param,";
            }
        }
        $subStrParams = substr($strParams, 0, -1);
        eval("(new {$this->class})->{$this->method}($subStrParams);");
    }

}
