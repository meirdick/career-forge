<?php

use App\Ai\Agents\GapAnalyzer;
use App\Ai\Agents\JobAnalyzer;
use App\Ai\Agents\LinkIndexer;
use App\Ai\Agents\ResumeGenerator;
use App\Ai\Agents\ResumeParser;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

function assertArrayPropertiesHaveItems(array $schema, string $path = ''): void
{
    foreach ($schema as $key => $value) {
        $currentPath = $path ? "{$path}.{$key}" : $key;

        if (is_array($value)) {
            if (isset($value['type']) && $value['type'] === 'array') {
                expect($value)
                    ->toHaveKey('items')
                    ->and($value['items'])->not->toBeEmpty();
            }

            assertArrayPropertiesHaveItems($value, $currentPath);
        }
    }
}

it('generates array properties with items for ResumeParser', function () {
    $schema = (new ResumeParser)->schema(new JsonSchemaTypeFactory);

    $serialized = array_map(fn ($type) => $type->toArray(), $schema);

    assertArrayPropertiesHaveItems($serialized);
});

it('generates array properties with items for JobAnalyzer', function () {
    $schema = (new JobAnalyzer)->schema(new JsonSchemaTypeFactory);

    $serialized = array_map(fn ($type) => $type->toArray(), $schema);

    assertArrayPropertiesHaveItems($serialized);
});

it('generates array properties with items for GapAnalyzer', function () {
    $schema = (new GapAnalyzer)->schema(new JsonSchemaTypeFactory);

    $serialized = array_map(fn ($type) => $type->toArray(), $schema);

    assertArrayPropertiesHaveItems($serialized);
});

it('generates array properties with items for LinkIndexer', function () {
    $schema = (new LinkIndexer)->schema(new JsonSchemaTypeFactory);

    $serialized = array_map(fn ($type) => $type->toArray(), $schema);

    assertArrayPropertiesHaveItems($serialized);
});

it('generates array properties with items for ResumeGenerator', function () {
    $schema = (new ResumeGenerator)->schema(new JsonSchemaTypeFactory);

    $serialized = array_map(fn ($type) => $type->toArray(), $schema);

    assertArrayPropertiesHaveItems($serialized);
});
