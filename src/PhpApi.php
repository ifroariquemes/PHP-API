<?php

namespace PhpApi;

use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Saxulum\AnnotationManager\Manager\AnnotationManager;
use PhpApi\Annotations\Api;
use PhpApi\Annotations\HttpMethod;

/**
 * This classe resolves URL routing by using annotations on methods. 
 * It specifies a pattern that, when matched, triggers that operation.
 * 
 * @package PhpApi
 * @copyright (c) 2017, Federal Institute of Rondonia
 * @license http://gnu.org/licenses/lgpl.txt LGPL-3.0+
 * @author Natanael Simoes <natanael.simoes@ifro.edu.br>
 * @since Release 0.1.0
 * @link https://github.com/ifroariquemes/PHP-API Github repository
 */
class PhpApi
{

    use \FlorianWolters\Component\Util\Singleton\SingletonTrait;

    /**
     * The file path where the @Api annotation is setup
     * @var string
     */
    const API_ANNOTATION_PATH = '/Annotations/Api.php';

    /**
     * The file path where the @Method annotation is setup
     * @var string
     */
    const METHOD_ANNOTATION_PATH = '/Annotations/HttpMethod.php';

    /**
     * The directory this class will iterate looking for @Api annotation
     * @var string
     */
    private $sourceDir;

    /**
     * The request string captured from URL
     * @var string
     */
    private $request;

    /**
     * The request method (GET, PUT, DELETE...)
     * @var string 
     */
    private $requestMethod;

    /**
     * Holds all @Api routes.
     * It stores the <i>pattern</i> that triggers the route, <i>class</i> and
     * <i>function</i> that will be executed, moreover the specific HTTP 
     * <i>method</i> used for that function
     * @var Route[]
     */
    private $routes = array();

    /**
     * Stores only the patterns that triggers routes (to improve search speed)
     * @var array
     */
    private $patterns = array();

    /**
     * Initilizes the object loading routes and patterns. Do not instantiate
     * this directly. Instead, use the 
     * @param string $sourceDir The directory this class will iterate looking for @Api annotation
     * @uses PhpApi::generateRoutes
     */
    protected function __construct($sourceDir)
    {
        Token::authenticate();
        if (!file_exists($sourceDir)) {
            throw new \Exception('Source directory does not exists.');
        }
        $this->sourceDir = $sourceDir;
        $serverPHPSelf = filter_input(INPUT_SERVER, 'PHP_SELF');
        $serverPHPRequest = filter_input(INPUT_SERVER, 'REQUEST_URI');
        $this->requestMethod = filter_input(INPUT_SERVER, 'REQUEST_METHOD');
        $selfPart = substr($serverPHPSelf, 0, strrpos($serverPHPSelf, '/') + 1);
        $requestUri = str_replace($selfPart, '', $serverPHPRequest);
        $request = ($pos = strpos($requestUri, '?')) ?
                substr($requestUri, 0, $pos) : $requestUri;
        $this->request = (substr($request, -1) === '/') ?
                substr($request, 0, -1) : $request;
        $this->generateRoutes();
    }

    /**
     * Start the PhpApi library and evaluates the actual URL
     * @param string $sourceDir The directory this class will iterate looking for @Api annotation
     * @uses PhpApi::getInstance
     * @uses PhpApi::evaluateURL
     */
    public static function start($sourceDir)
    {
        self::getInstance($sourceDir)->evaluateURL();
    }

    /**
     * Generates routes that triggers specific class methods
     * @uses PhpApi::getAllClasses
     * @uses \Saxulum\AnnotationManager\Helper\ClassInfo::getMethodInfos
     * @uses AnnotationReader::getMethodAnnotation
     * @uses PhpApi::addRoute
     */
    private function generateRoutes()
    {
        AnnotationRegistry::registerFile(__DIR__ . self::API_ANNOTATION_PATH);
        AnnotationRegistry::registerFile(__DIR__ . self::METHOD_ANNOTATION_PATH);
        $annotationReader = new AnnotationReader();
        foreach ($this->getAllClasses() as $class) {
            foreach ($class->getMethodInfos() as $method) {
                $method = new \ReflectionMethod($class->getName(), $method->getName());
                $apiPattern = $annotationReader->getMethodAnnotation($method, new Api);
                $httpMethod = $annotationReader->getMethodAnnotation($method, new HttpMethod);
                if (!is_null($apiPattern)) {
                    $this->addRoute($method, $apiPattern, $httpMethod);
                }
            }
        }
    }

    /**
     * Adds the route and pattern for a given $method using a specific
     * HTTP request method if available
     * @param \ReflectionMethod $method The class method that will be triggered
     * @param Api $api The $method @Api annotation
     * @param HttpMethod $httpMethod The $method @HttpMethod Annotation
     */
    private function addRoute(\ReflectionMethod &$method, Api &$api, HttpMethod &$httpMethod = null)
    {
        if (!is_null($api) && self::validatePattern($api->pattern)) {
            $patterns = explode(';', $api->pattern);
            foreach ($patterns as $pattern) {
                array_push($this->routes, new Route($pattern, $method->class, $method->name, $httpMethod->name ?? null));
                array_push($this->patterns, $pattern);
            }
        }
    }

    /**
     * Returns all classes within the source directory
     * @return \Saxulum\AnnotationManager\Helper\ClassInfo[]
     * @uses AnnotationManager::buildClassInfosBasedOnPath
     */
    private function getAllClasses(): array
    {
        $annotationReader = new SimpleAnnotationReader();
        $annotationManager = new AnnotationManager($annotationReader);
        return $annotationManager->buildClassInfosBasedOnPath($this->sourceDir);
    }

    /**
     * Validates @Api pattern string
     * @param string $pattern The pattern
     * @return boolean If the pattern is valid
     */
    private static function validatePattern(string $pattern): bool
    {
        if (substr($pattern, -1) === '/' ||
                strpos('\\', $pattern) !== false ||
                strpos(' ', $pattern) !== false) {
            $patternRules = <<<EOT
Route pattern $pattern not following routing rules: patterns cannot end with /
    and have any whitespaces or \\
EOT;
            http_response_code(400);
            echo json_encode(['message' => $patternRules]);
            exit;
        }
        return true;
    }

    /**
     * Returns the route for a given pattern
     * @param string $pattern The pattern
     * @return Route The route
     * @uses Route::acceptPatternAndHttpMethod
     */
    private function getRoute(string $pattern): Route
    {
        $onlyRoute = false;
        foreach ($this->routes as $route) {
            if ($route->acceptPattern($pattern) && $route->acceptHttpMethod($this->requestMethod)) {
                return $route;
            }
        }
        http_response_code(501); // If reaches here, the real route does not accept the HTTP method requested
        echo json_encode(['message' => "The method $this->requestMethod is not implemented for this request."]);
        exit;
    }

    /**
     * Evaluates URL looking for a route/pattern that matches the request
     * @uses PhpApi::getUniquePatterns
     * @uses PhpApi::getRoute
     * @uses PhpApi::searchRequestPattern
     * @uses PhpApi::processRequestPattern
     */
    private function evaluateURL()
    {
        if (empty($this->request)) { //index
            echo str_replace('\\', '', json_encode($this->getUniquePatterns()));
            exit;
        } elseif (in_array($this->request, $this->patterns)) {
            $this->getRoute($this->request)->execute();
        } else {
            $pattern = $this->searchRequestPattern();
            $this->processRequestPattern($pattern);
        }
    }

    /**
     * Returns all patterns used in @Api. This method returns just one ocurrence
     * of the pattern even if it is used in multiple locations (with distinctive
     * HTTP request methods)
     * @return array
     */
    private function getUniquePatterns(): array
    {
        $pM = array();
        foreach ($this->patterns as $pattern) {
            if (!in_array($pattern, $pM)) {
                array_push($pM, $pattern);
            }
        }
        return $pM;
    }

    /**
     * Executes the class method for the given pattern 
     * @param string $pattern
     * @uses PhpApi::explodePattern
     * @uses PhpApi::getRoute
     * @uses Route::getMethodParams
     * @uses Route::execute
     */
    private function processRequestPattern(string $pattern)
    {
        $arRequest = $this->explodePattern($this->request);
        $arPattern = $this->explodePattern($pattern);
        $route = $this->getRoute($pattern);
        $refParams = $route->getMethodParams();
        $params = array();
        for ($i = 0, $max = count($arPattern); $i < $max; $i++) {
            if (substr($arPattern[$i], 0, 1) === '$') {
                $index = array_search(substr($arPattern[$i], 1), $refParams);
                $params[$index] = $arRequest[$i];
            }
        }
        array_multisort($params);
        $route->execute($params);
    }

    /**
     * Search for patterns that looks like the original request
     * @return string The pattern
     * @uses PhpApi::explodePattern
     */
    private function searchRequestPattern(): string
    {
        $arRequest = $this->explodePattern($this->request);
        $countRequest = count($arRequest);
        $exPatterns = array_map('PhpApi\PhpApi::explodePattern', $this->patterns); // explode all patterns
        $coPatterns = array_filter($exPatterns, function ($elem) use ($countRequest) { // filter where count is equal to request
            return count($elem) === $countRequest;
        });
        $index = 0;
        do {
            $coPatterns = array_filter($coPatterns, function ($elem) use ($arRequest, $index) { // filter where all position are equal, ignores when expecting variable
                return (substr($elem[$index], 0, 1) === '$') ? true : $elem[$index] === $arRequest[$index];
            });
            $index++;
        } while (array_key_exists($index, $arRequest));
        if (count($coPatterns) === 0) { // found no patterns as requested
            http_response_code(404);
            echo json_encode(['message' => 'Resource not found.']);
            exit;
        } else {
            return $this->patterns[array_keys($coPatterns)[0]];
        }
    }

    /**
     * Explodes a pattern or route into a array using a slash as delimiter
     * @param string $pattern The pattern or route
     * @return array The exploded string
     */
    private static function explodePattern(string $pattern): array
    {
        return explode('/', $pattern);
    }

}
