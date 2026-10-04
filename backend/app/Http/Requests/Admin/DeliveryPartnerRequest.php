<?php

namespace App\Http\Requests\Admin;

use App\Models\DeliveryPartner;
use App\Support\AdminAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAccess::canManageOrders();
    }

    public function rules(): array
    {
        $partnerId = $this->route('delivery_partner')?->id;
        $creatingNewUser = $this->boolean('create_new_user');

        return [
            // Creating: either pick an existing partner-role user, or fill in
            // the name/phone/password fields below to create one on the fly
            // — this admin panel previously had no way to originate a rider
            // login at all, only attach a partner profile to a user that
            // already existed some other way.
            'user_id' => [$creatingNewUser ? 'prohibited' : 'required', 'nullable', 'integer', 'exists:users,id'],
            'new_user_name' => [$creatingNewUser ? 'required' : 'prohibited', 'string', 'max:255'],
            'new_user_phone' => [
                $creatingNewUser ? 'required' : 'prohibited',
                'string',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('users', 'phone'),
            ],
            'new_user_password' => [$creatingNewUser ? 'required' : 'prohibited', 'string', 'min:8'],
            'partner_code' => ['required', 'string', 'max:20', Rule::unique('delivery_partners', 'partner_code')->ignore($partnerId)],
            'photo_url' => ['nullable', 'string', 'max:255'],
            'vehicle_type' => ['required', Rule::in([
                DeliveryPartner::VEHICLE_TRAIN,
                DeliveryPartner::VEHICLE_BUS,
                DeliveryPartner::VEHICLE_BIKE,
                DeliveryPartner::VEHICLE_ON_FOOT,
            ])],
            'id_proof_type' => ['nullable', 'string', 'max:50'],
            'id_proof_number_encrypted' => ['nullable', 'string'],
            'verification_status' => ['required', Rule::in([
                DeliveryPartner::VERIFICATION_PENDING,
                DeliveryPartner::VERIFICATION_VERIFIED,
                DeliveryPartner::VERIFICATION_REJECTED,
            ])],
            'is_active' => ['required', 'boolean'],
            'rating_avg' => ['required', 'numeric', 'min:0', 'max:5'],
            'completed_deliveries_count' => ['required', 'integer', 'min:0'],
            'current_home_city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'create_new_user' => $this->boolean('create_new_user'),
            'is_active' => $this->boolean('is_active'),
            'current_home_city_id' => $this->input('current_home_city_id') !== '' ? $this->input('current_home_city_id') : null,
        ]);
    }
}
