<?php

namespace App\Http\Requests\Links;

use App\Enums\ItemLinkType;
use App\Models\ItemLink;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreItemLinkRequest extends FormRequest
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
     * The morph columns carry no foreign keys, so both ends are checked here:
     * the type has to be a registered module and the id has to exist in it.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modules = array_keys(Relation::morphMap());

        return [
            'source_type' => ['required', Rule::in($modules)],
            'source_id' => ['required', 'integer', $this->existsInModule('source_type')],
            'target_type' => ['required', Rule::in($modules)],
            'target_id' => ['required', 'integer', $this->existsInModule('target_type')],
            'link_type' => ['required', new Enum(ItemLinkType::class)],
            'note' => ['nullable', 'string', 'max:255'],
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

                if ($this->linksToItself()) {
                    $validator->errors()->add('target_id', __('A record cannot be linked to itself.'));

                    return;
                }

                if ($this->duplicatesAnExistingLink()) {
                    $validator->errors()->add('link_type', __('That link already exists between these two records.'));
                }
            },
        ];
    }

    /**
     * A row with the given id must exist in the table the type names.
     */
    private function existsInModule(string $typeField): ValidationRule|string
    {
        $model = $this->modelFor($this->input($typeField));

        if (! $model instanceof Model) {
            return 'integer';
        }

        return Rule::exists($model->getTable(), $model->getKeyName());
    }

    private function linksToItself(): bool
    {
        return $this->input('source_type') === $this->input('target_type')
            && (int) $this->input('source_id') === (int) $this->input('target_id');
    }

    private function duplicatesAnExistingLink(): bool
    {
        return ItemLink::query()
            ->where('source_type', $this->input('source_type'))
            ->where('source_id', $this->input('source_id'))
            ->where('target_type', $this->input('target_type'))
            ->where('target_id', $this->input('target_id'))
            ->where('link_type', $this->input('link_type'))
            ->exists();
    }

    private function modelFor(mixed $type): ?Model
    {
        if (! is_string($type)) {
            return null;
        }

        $class = Relation::getMorphedModel($type);

        return is_string($class) && class_exists($class) ? new $class : null;
    }
}
