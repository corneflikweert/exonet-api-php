<?php

declare(strict_types=1);

namespace Exonet\Api;

use Exonet\Api\Structures\Resource;
use Exonet\Api\Structures\ResourceIdentifier;
use Exonet\Api\Structures\ResourceSet;

/**
 * This class is responsible for building a valid API request that can be passed to the Connector.
 */
class Request
{
    /**
     * @var string The resource name.
     */
    private $resource;

    /**
     * @var Connector The connector instance.
     */
    private $connector;

    /**
     * @var mixed[] Optional query string parameters.
     */
    private $queryStringParameters = [
        'page' => [
            'size' => null,
            'number' => null,
        ],
        'filter' => [],
    ];

    /**
     * Request constructor.
     *
     * @param null|string                $resource  The resource to get.
     * @param \Exonet\Api\Connector|null $connector Optional connector instance to use.
     */
    public function __construct(string $resource, ?Connector $connector = null)
    {
        $this->resource = $resource;
        $this->connector = $connector ?? new Connector();
    }

    /**
     * Create a resource identifier for a request.
     *
     * @param string $id The identifier for the resource.
     *
     * @return ResourceIdentifier The resource identifier.
     */
    public function id(string $id)
    {
        return new ResourceIdentifier($this->resource, $id);
    }

    /**
     * Get the resource or, if specified, the resource that belongs to the ID.
     *
     * @param null|string $id Optional ID to get a specific resource.
     *
     * @return \Exonet\Api\Structures\Resource|\Exonet\Api\Structures\ResourceSet The requested data transformed
     *                                                                                  to a single or multiple resources.
     *@throws \Exonet\Api\Exceptions\ExonetApiException If there was a problem with the request.
     *
     */
    public function get(?string $id = null)
    {
        return $this->connector->get($this->prepareUrl($id));
    }

    /**
     * Post new data to the API.
     *
     * @param array $payload The payload to post to the API.
     *
     * @return ApiResource|ApiResourceIdentifier|ApiResourceSet The parsed response transformed to resoures.
     */
    public function post(array $payload)
    {
        return $this->connector->post(
            trim($this->resource, '/'),
            $payload
        );
    }

    /**
     * Delete a resource.
     *
     * @param string $id The ID of the resource to delete.
     */
    public function delete(string $id)
    {
        $this->connector->delete(
            trim($this->resource, '/').'/'.$id
        );
    }

    /**
     * Set the page size for the request. This is the maximum number of resources that is returned in a single call.
     *
     * @param int $pageSize The page size.
     *
     * @return self This current Request instance.
     */
    public function size(int $pageSize) : self
    {
        $this->queryStringParameters['page']['size'] = $pageSize;

        return $this;
    }

    /**
     * Set the page number for the request.
     *
     * @param int $pageNumber The page number.
     *
     * @return self This current Request instance.
     */
    public function page(int $pageNumber) : self
    {
        $this->queryStringParameters['page']['number'] = $pageNumber;

        return $this;
    }

    /**
     * Set a filter for the request.
     *
     * @param string $name  The filter name.
     * @param mixed  $value The filter value. Default: true.
     *
     * @return self This current Request instance.
     */
    public function filter(string $name, $value = true) : self
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        $this->queryStringParameters['filter'][$name] = $value;

        return $this;
    }

    /**
     * Prepare the URL for the resource, including (optional) ID and query string parameters. Remove
     * excessive slashes and add query string parameters.
     *
     * @param null|string $id The resource ID to get.
     *
     * @return string The fully prepared URL.
     */
    private function prepareUrl(?string $id = null) : string
    {
        $url = trim($this->resource, '/');
        if ($id) {
            $url .= '/'.$id;
        }

        $params = http_build_query($this->queryStringParameters);
        if (!empty($params)) {
            $url .= '?'.$params;
        }

        return $url;
    }
}
