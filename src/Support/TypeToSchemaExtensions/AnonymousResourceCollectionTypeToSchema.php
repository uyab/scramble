<?php

namespace Dedoc\Scramble\Support\TypeToSchemaExtensions;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType as OpenApiArrayType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeWalker;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

class AnonymousResourceCollectionTypeToSchema extends TypeToSchemaExtension
{
    public function shouldHandle(Type $type)
    {
        return $type instanceof Generic
            && $type->isInstanceOf(AnonymousResourceCollection::class)
            && count($type->genericTypes) === 1;
    }

    /**
     * @param  Generic  $type
     */
    public function toSchema(Type $type)
    {
        if (! $collectingResourceType = $this->getCollectingResourceType($type)) {
            return null;
        }

        return (new OpenApiArrayType)
            ->setItems($this->openApiTransformer->transform($collectingResourceType));
    }

    /**
     * @param  Generic  $type
     */
    public function toResponse(Type $type)
    {
        // In case of paginated resource, we want to get pagination response.
        if ($type->genericTypes[0] instanceof Generic) {
            return $this->openApiTransformer->toResponse($type->genericTypes[0]);
        }

        if (! $collectingResourceType = $this->getCollectingResourceType($type)) {
            return null;
        }

        $jsonResourceOpenApiType = $this->openApiTransformer->transform($collectingResourceType);
        $responseWrapKey = ($collectingResourceType->name)::$wrap;

        $type = $responseWrapKey
            ? (new OpenApiObjectType)->addProperty($responseWrapKey, $jsonResourceOpenApiType)->setRequired([$responseWrapKey])
            : (new OpenApiArrayType)->setItems($jsonResourceOpenApiType);

        return Response::make(200)
            ->description('Array of `'.$this->components->uniqueSchemaName($collectingResourceType->name).'`')
            ->setContent('application/json', Schema::fromType($type));
    }

    private function getCollectingResourceType(Generic $type): ?ObjectType
    {
        // In case of paginated resource, we still want to get to the underlying JsonResource.
        return (new TypeWalker)->first(
            $type->genericTypes[0],
            fn (Type $t) => $t->isInstanceOf(JsonResource::class),
        );
    }
}
