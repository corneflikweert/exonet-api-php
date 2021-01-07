<?php

declare(strict_types=1);

namespace Exonet\Api\Structures;

use Exonet\Api\Client;
use Exonet\Api\Request;

/**
 * An ApiResourceID is a way to identify a single resource.
 */
class ApiResourceIdentifier
{
    /**
     * @var string The resource type.
     */
    private $resourceType;

    /**
     * @var string The resource ID.
     */
    private $id;

    /**
     * @var Request A request instance to make calls to the API.
     */
    protected $request;

    /**
     * @var Relation[]|null The relationships for this resource.
     */
    protected $relationships;

    /**
     * @var string[] Array to keep track of relationships that are changed.
     */
    protected $changedRelationships = [];

    /**
     * @var Client|null The initialised API client.
     */
    protected $apiClient;

    /**
     * ApiResourceIdentifier constructor.
     *
     * @param string      $resourceType The resource type.
     * @param string|null $id           The resource ID.
     * @param Client|null $apiClient    The initialised API client.
     */
    public function __construct(string $resourceType, ?string $id = null, Client $apiClient = null)
    {
        $this->resourceType = $resourceType;
        $this->id = $id;
        $this->apiClient = $apiClient;

        $this->request = new Request($resourceType, $apiClient);
    }

    /**
     * Get the resource type.
     *
     * @return string The resource type.
     */
    public function type(): string
    {
        return $this->resourceType;
    }

    /**
     * Get the resource Id.
     *
     * @return string|null The resource Id.
     */
    public function id(): ?string
    {
        return $this->id;
    }

    /**
     * Make a GET request to the resource.
     *
     * @return ApiResource|ApiResourceSet A resource or resource set.
     */
    public function get()
    {
        return $this->request->get($this->id);
    }

    /**
     * Delete this resource from the API.  Will return 'true' when successful. If not successful an exception is thrown.
     *
     * @return true When the delete was successful.
     */
    public function delete(): bool
    {
        // If there are no changed relationships, perform a 'normal' delete.
        if (empty($this->changedRelationships)) {
            return $this->request->delete($this->id());
        }

        // If there are changed relationships, transform them to JSON and send a DELETE to the relationship endpoint.
        foreach ($this->changedRelationships as $relationship) {
            $relationData = $this->relationship($relationship);
            if (is_array($relationData)) {
                array_walk($relationData, function (&$relationItem) {
                    $relationItem = $relationItem->toJson();
                });
            } else {
                $relationData = $relationData->toJson();
            }

            $this->request->delete($this->id().'/relationships/'.$relationship, ['data' => $relationData]);
        }

        return true;
    }

    /**
     * Get a relation definition to another resource.
     *
     * @param string $name The name of the relation.
     *
     * @return Relation|Relationship
     */
    public function related($name)
    {
        return new Relation($name, $this->type(), $this->id(), $this->apiClient);
    }

    /**
     * Get a relationship definition to another resource.
     *
     * @param string                                        $name     The name of the relationship.
     * @param ApiResourceIdentifier|ApiResourceIdentifier[] $resource
     *
     * @return Relation|Relationship|$this The requested relation data or the current resource when setting a relation.
     */
    public function relationship(string $name, $resource = null)
    {
        // If there are is only a single argument, get the relation.
        if (func_num_args() === 1) {
            // Check if the relationship is already defined. If not, create it now.
            if (!isset($this->relationships[$name])) {
                $this->relationships[$name] = new Relationship($name, $this->type(), $this->id(), $this->apiClient);
            }

            return $this->relationships[$name];
        }

        // Set the relation data.
        if (is_array($resource)) {
            foreach ($resource as $resourceIdentifier) {
                $this->relationships[$name][] = $resourceIdentifier;
            }
        } else {
            $this->relationships[$name] = $resource;
        }

        $this->changedRelationships[] = $name;

        return $this;
    }

    /**
     * Transform the set identifiers for this resource to an array that can be used for JSON.
     */
    protected function toJson(): array
    {
        return [
            'type' => $this->type(),
            'id' => $this->id(),
        ];
    }
}
