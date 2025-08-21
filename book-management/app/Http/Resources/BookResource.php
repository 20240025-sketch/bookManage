<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'isbn' => $this->isbn,
            'category' => $this->category,
            'acceptance_date' => $this->acceptance_date?->format('Y-m-d'),
            'acceptance_type' => $this->acceptance_type,
            'acceptance_source' => $this->acceptance_source,
            'discard' => $this->discard,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
