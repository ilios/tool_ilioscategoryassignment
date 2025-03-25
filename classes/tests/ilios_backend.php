<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mock Ilios backend for Behat tests.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_ilioscategoryassignment\tests;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This Guzzle custom handler provides a mock Ilios backend for acceptance testing.
 *
 * @package    tool_ilioscategoryassignment
 * @copyright  The Regents of the University of California
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ilios_backend {

    /**
     * @var array A list of mock Ilios school data.
     */
    protected array $mockschools;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->mockschools = [
            ['id' => 1, 'title' => 'Medicine'],
            ['id' => 2, 'title' => 'Pharmacy'],
        ];
    }

    /**
     * Responds to the given request with the appropriate response.
     * @param RequestInterface $request The given request.
     * @return PromiseInterface A promise resolving to a response object.
     */
    public function __invoke(RequestInterface $request): PromiseInterface {
        $uri = $request->getUri();
        $path = $uri->getPath();

        // Route by path.
        $response = match($path) {
            '/api/v3/schools' => $this->get_all_schools_response(),
            default => new Response(500),
        };

        // The response must be wrapped in a promise.
        // Create one and resolve it immediately.
        // See https://github.com/guzzle/promises?tab=readme-ov-file#synchronous-wait.
        $promise = new Promise(function () use (&$promise, $response) {
            $promise->resolve($response);
        });
        $promise->wait(false);

        // Return resolved promise containing the applicable response.
        return $promise;
    }

    /**
     * Returns are mocked response for the schools endpoint on the Ilios API.
     * @link https://demo.iliosproject.org/api/doc#operations-Schools-get_app_api_schools_getall
     * @return ResponseInterface
     */
    protected function get_all_schools_response(): ResponseInterface {
        return new Response(200, [], json_encode([
            'schools' => $this->mockschools,
        ]));
    }
}
