<?php

namespace Ritechoice23\Taggable\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaggableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->taggable_id,
            'type' => $this->taggable_type,
            'tagged_at' => $this->created_at,
            'tag' => new TagResource($this->whenLoaded('tag')),
            'taggable' => $this->whenLoaded('taggable', function () {
                return $this->taggable;
            }),
        ];
    }
}
