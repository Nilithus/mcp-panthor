<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace QL\Panthor\ErrorHandling\ContentHandler;

use ErrorException;
use Mockery;
use PHPUnit_Framework_TestCase;
use Slim\Http\Environment;
use Slim\Http\Request;
use Slim\Http\Response;

class PlainTextContentHandlerTest extends PHPUnit_Framework_TestCase
{
    private $request;
    private $response;

    public function setUp()
    {
        $this->request = Request::createFromEnvironment(Environment::mock());
        $this->response = new Response;
    }

    public function testNotFound()
    {
        $handler = new PlainTextContentHandler;
        $response = $handler->handleNotFound($this->request, $this->response);

        $expected = <<<HTML
HTTP/1.1 404 Not Found
Content-Type: text/plain

Not Found.
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowed()
    {
        $handler = new PlainTextContentHandler;
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 405 Method Not Allowed
Content-Type: text/plain

Method not allowed.
Allowed methods: PATCH, STEVE
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testNotAllowedSets200StatusIfOptionsRequest()
    {
        $this->request = $this->request->withMethod('OPTIONS');

        $handler = new PlainTextContentHandler;
        $response = $handler->handleNotAllowed($this->request, $this->response, ['PATCH', 'STEVE']);

        $expected = <<<HTML
HTTP/1.1 200 OK
Content-Type: text/plain

Method not allowed.
Allowed methods: PATCH, STEVE
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleException()
    {
        $ex = new ErrorException('exception message');

        $handler = new PlainTextContentHandler;
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: text/plain

Application Error
HTML;

        $this->assertSame($expected, (string) $response);
    }

    public function testHandleExceptionWithErrorDetails()
    {
        $ex = new ErrorException('exception message');

        $handler = new PlainTextContentHandler(true);
        $response = $handler->handleException($this->request, $this->response, $ex);

        $expected = <<<HTML
HTTP/1.1 500 Internal Server Error
Content-Type: text/plain

Application Error
exception message
HTML;

        $rendered = (string) $response;
        $this->assertContains($expected, $rendered);
        $this->assertContains('ErrorHandling/ContentHandler/PlainTextContentHandlerTest.php:96', $rendered);
        $this->assertContains('Error Details:', $rendered);
    }
}
