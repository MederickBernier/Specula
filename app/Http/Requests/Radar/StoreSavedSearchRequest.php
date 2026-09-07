<?php

namespace App\Http\Requests\Radar;

use App\Enums\TriageStatus;
use App\Models\SavedSearch;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreSavedSearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('saved_searches', 'name')->where('user_id', $this->user()?->id),
            ],
            'q' => ['nullable', 'string', 'max:255'],
            'feed' => ['nullable', 'integer', 'exists:feed_sources,id'],
            'status' => ['nullable', new Enum(TriageStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => __('You already have a saved search with that name.'),
        ];
    }

    /**
     * The filters to store, with the empty ones dropped so a saved search only
     * carries what it actually narrows.
     *
     * @return array<string, string>
     */
    public function filters(): array
    {
        $filters = [];

        foreach (SavedSearch::FILTER_KEYS as $key) {
            $value = trim((string) $this->validated($key, ''));

            if ($value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }
}
