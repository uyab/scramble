<?php

namespace Dedoc\Scramble\Support\Generator;

use Dedoc\Scramble\Support\Generator\Types\Type;

class Reference extends Type
{
    public string $referenceType;

    public string $fullName;

    private Components $components;

    public function __construct(string $referenceType, string $fullName, Components $components)
    {
        $this->referenceType = $referenceType;
        $this->fullName = $fullName;
        $this->components = $components;
    }

    public function toArray()
    {
        return [
            '$ref' => "#/components/{$this->referenceType}/{$this->components->uniqueSchemaName($this->fullName)}",
        ];
    }
}
