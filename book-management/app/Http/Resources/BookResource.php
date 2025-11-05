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
            'volume_number' => $this->volume_number,
            'title_transcription' => $this->title_transcription,
            'author' => $this->author,
            'publisher' => $this->publisher,
            'published_date' => $this->published_date?->format('Y-m-d'),
            'publication_year' => $this->publication_year,
            'isbn' => $this->isbn,
            'pages' => $this->pages,
            'price' => $this->price,
            'ndc' => $this->ndc,
            'category' => $this->category,
            'quantity' => $this->quantity ?? 1,
            'acceptance_date' => $this->acceptance_date?->format('Y-m-d'),
            'acceptance_type' => $this->acceptance_type,
            'acceptance_source' => $this->acceptance_source,
            'discard' => $this->discard,
            'storage_location' => $this->storage_location,
            'current_borrowed_count' => $this->borrows->where('returned_date', null)->count(),
            'available_quantity' => ($this->quantity ?? 1) - $this->borrows->where('returned_date', null)->count(),
            'is_borrowed' => $this->borrows->contains(function ($borrow) {
                return $borrow->returned_date === null;
            }),
            'is_fully_borrowed' => (($this->quantity ?? 1) - $this->borrows->where('returned_date', null)->count()) <= 0,
            'current_borrow' => $this->borrows->first(function ($borrow) {
                return $borrow->returned_date === null;
            }),
            'borrow_history' => $this->borrows->map(function ($borrow) {
                // 日付や生徒データが欠損している場合に備えて安全に処理
                $borrowedDate = $borrow->borrowed_date ? $borrow->borrowed_date->format('Y-m-d') : null;
                $returnedDate = $borrow->returned_date ? $borrow->returned_date->format('Y-m-d') : null;

                $student = $borrow->student;
                $studentData = $student ? [
                    'id' => $student->id,
                    'name' => $student->name,
                    'grade' => $student->grade,
                    'class' => $student->class
                ] : null;

                $duration = '貸出中';
                if ($borrow->returned_date && $borrow->borrowed_date) {
                    try {
                        $duration = $borrow->borrowed_date->diffInDays($borrow->returned_date) . '日間';
                    } catch (\Throwable $e) {
                        // 計算できない場合は既定値を使う
                        $duration = '不明';
                    }
                }

                return [
                    'id' => $borrow->id,
                    'borrowed_date' => $borrowedDate,
                    'returned_date' => $returnedDate,
                    'student' => $studentData,
                    'duration' => $duration
                ];
            })->take(3),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
