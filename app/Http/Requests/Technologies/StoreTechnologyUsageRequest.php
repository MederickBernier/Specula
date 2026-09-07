<?php

namespace App\Http\Requests\Technologies;

use App\Models\TechnologyUsage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTechnologyUsageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The morph columns carry no foreign key, so the record at the other end is
     * checked here: the type has to be something that can carry a technology,
     * and the id has to exist in it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'technology_id' => ['required', 'integer', 'exists:technologies,id'],
            'usable_type' => ['required', Rule::in(array_keys(TechnologyUsage::carriers()))],
            'usable_id' => ['required', 'integer', $this->existsInCarrier()],
            'version' => ['nullable', 'string', 'max:64'],
            'role' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $exists = TechnologyUsage::query()
                    ->where('technology_id', $this->input('technology_id'))
                    ->where('usable_type', $this->input('usable_type'))
                    ->where('usable_id', $this->input('usable_id'))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'technology_id',
                        __('That is already recorded here. Edit the entry to change the version.'),
                    );
                }
            },
        ];
    }

    private function existsInCarrier(): ValidationRule|string
    {
        $class = TechnologyUsage::carriers()[$this->input('usable_type')] ?? null;

        if ($class === null) {
            return 'integer';
        }

        $model = new $class;

        return Rule::exists($model->getTable(), $model->getKeyName());
    }
}
