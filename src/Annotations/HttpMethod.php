<?php

namespace PhpApi\Annotations;

/**
 * The annotation to specify a HTTP request method that triggers a class method.
 * @Annotation
 * @Target("METHOD")
 * @package PhpApi
 * @copyright (c) 2017, Federal Institute of Rondonia
 * @license http://gnu.org/licenses/lgpl.txt LGPL-3.0+
 * @author Natanael Simoes <natanael.simoes@ifro.edu.br>
 * @since Release 0.1.0
 * @link https://github.com/ifroariquemes/PHP-API Github repository
 */
class HttpMethod
{

    /**
     * The HTTP request method should be:
     * 
     * <ul>
     *  <li>GET</li>
     *  <li>POST</li>
     *  <li>PUT</li>
     *  <li>DELETE</li>
     *  <li>OPTIONS</li>
     *  <li>HEAD</li>
     *  <li>TRACE</li>
     *  <li>CONNECT</li>
     * </ul>
     * 
     * Add more HTTP request methods to the same class method by using commas.
     * If a class method has no @Method, then any HTTP request method will
     * execute that class method.
     *
     * <pre>
     * *@Api("my/route") // put this in javadoc style
     * // No @HttpMethod annotation, accepts all HTTP request methods
     * public function myRoute() {
     *  // code
     * }</pre>
     * 
     * <pre>
     * *@Api("route/with/$var") // put this in javadoc style
     * *@HttpMethod("PUT") // accepts only PUT requests
     * public function routeWith($var) {
     *  // code
     * }</pre>
     * 
     * <pre>
     * *@Api("another/$id/maybe/$var") // put this in javadoc style
     * *@HttpMethod("GET,POST") // accepts only GET and POST requests
     * public function routeAnother($id, $var) {
     *  // code
     * }</pre>
     * @var string 
     */
    public $name;

}
