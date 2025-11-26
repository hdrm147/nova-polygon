<?php

namespace Hdrm147\Polygon;

use App\Models\Area;
use Clickbar\Magellan\IO\Parser\Geojson\GeojsonParser;
use Laravel\Nova\Fields\Field;

class Polygon extends Field
{
    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'polygon';

    /**
     * @param Area $resource
     * @param string $attribute
     * @return mixed
     */
    protected function resolveAttribute($resource, string $attribute): mixed
    {
        /** @var \Clickbar\Magellan\Data\Geometries\Polygon $resource */
        $polygon = $resource->polygon;
        return $polygon;
    }

    public function fillModelWithData(object $model, mixed $value, string $attribute): void
    {
     $parser = app(GeojsonParser::class);
     $model->{$attribute} = $parser->parse($value);
    }
}
