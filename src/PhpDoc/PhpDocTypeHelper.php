<?php

namespace Dedoc\Scramble\PhpDoc;

use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\ArrayType;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\FloatType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\IntegerType;
use Dedoc\Scramble\Support\Type\IntersectionType;
use Dedoc\Scramble\Support\Type\NullType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\StringType;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\Type\UnknownType;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeItemNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\ArrayTypeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;

class PhpDocTypeHelper
{
    public static function toType(TypeNode $type)
    {
        if ($type instanceof IdentifierTypeNode) {
            return static::handleIdentifierNode($type);
        }

        if ($type instanceof ArrayShapeNode) {
            return new ArrayType(array_map(
                function (ArrayShapeItemNode $t) {
                    $keyName = $t->keyName instanceof IdentifierTypeNode
                        ? $t->keyName->name
                        : $t->keyName->value ?? null;

                    return new ArrayItemType_(
                        $keyName,
                        static::toType($t->valueType),
                        $t->optional,
                    );
                },
                $type->items,
            ));
        }

        if ($type instanceof ArrayTypeNode) {
            return new ArrayType([
                new ArrayItemType_(0, static::toType($type->type)),
            ]);
        }

        if ($type instanceof GenericTypeNode) {
            if ($type->type->name === 'array') {
                return static::toType(new ArrayShapeNode(
                    array_map(
                        fn ($type) => new ArrayShapeItemNode(null, false, $type),
                        $type->genericTypes,
                    )
                ));
            }

            if (! ($typeObject = static::toType($type->type)) instanceof ObjectType) {
                return $typeObject;
            }

            return new Generic(
                $typeObject,
                array_map(
                    fn ($type) => static::toType($type),
                    $type->genericTypes,
                )
            );
        }

        if ($type instanceof IntersectionTypeNode) {
            return new IntersectionType(array_map(
                fn ($t) => static::toType($t),
                $type->types,
            ));
        }

        if ($type instanceof UnionTypeNode) {
            return new Union(array_map(
                fn ($t) => static::toType($t),
                $type->types,
            ));
        }

        return new UnknownType('Unknown phpDoc type ['.$type.']');
    }

    private static function handleIdentifierNode(IdentifierTypeNode $type)
    {
        if ($type->name === 'string') {
            return new StringType;
        }
        if (in_array($type->name, ['float', 'double'])) {
            return new FloatType;
        }
        if (in_array($type->name, ['int', 'integer'])) {
            return new IntegerType;
        }
        if (in_array($type->name, ['bool', 'boolean', 'true', 'false'])) {
            return new BooleanType;
        }
        if ($type->name === 'scalar') {
            // @todo: Scalar variables are those containing an int, float, string or bool.
            return new StringType;
        }
        if ($type->name === 'array') {
            return new ArrayType;
        }
        if ($type->name === 'object') {
            return new ObjectType('\stdClass');
        }
        if ($type->name === 'null') {
            return new NullType;
        }
        if ($type->name === 'mixed') {
            return new UnknownType('PhpDoc mixed type');
        }

        return new ObjectType($type->name);
    }
}
