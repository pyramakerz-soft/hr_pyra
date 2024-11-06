<?php

namespace App\Http\Resources\Api;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IssueResource extends JsonResource
{
    public static function collectionWithPagination($paginator)
    {
        return [
            'data' => static::collection($paginator),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'previous_page_url' => $paginator->previousPageUrl(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [

            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'endTime' => Carbon::parse($this->clock_out)->format('H:i:s'),
            'dateOfIssue' => Carbon::parse($this->clock_out)->format('Y-m-d'),
            'clock_id' => $this->id,
            'user_id' => $this->user->id,
        ];
    }
}
