<?php
/*
 * Created At: 2026-04-30T05:26:35Z
 */

namespace Modules\Clocks\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Users\Models\User;

class StoreB2bFixedPermissionSlotRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = User::with('department')->find($value);
                    if (!$user || !$user->department || !str_contains(strtoupper($user->department->name), 'B2B')) {
                        $fail('The selected employee must belong to a B2B department.');
                    }
                },
            ],
            'day_of_week' => ['required', Rule::in(['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'saturday'])],
            'position' => ['required', Rule::in(['start', 'end'])],
            'slot_from' => ['required', 'date_format:H:i'],
            'slot_to' => ['required', 'date_format:H:i'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('slot_from')) {
            try {
                $slotFrom = Carbon::createFromFormat('H:i', $this->slot_from);
                $this->merge([
                    'slot_to' => $slotFrom->copy()->addHour()->format('H:i'),
                ]);
            } catch (\Exception $e) {
                // Let validation handle the format error
            }
        }
    }
}
