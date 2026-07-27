<?php

namespace App\Http\Requests\UserLog;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserVisitLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page_url' => ['required', 'string', 'max:2048'],
            'page_path' => ['nullable', 'string', 'max:1024'],
            'page_title' => ['nullable', 'string', 'max:255'],
            'referrer_url' => ['nullable', 'string', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'visited_at' => ['nullable', 'date'],
        ];
    }
}
