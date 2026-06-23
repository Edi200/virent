<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\RentalProfileUpdateRequest;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RentalProfileController extends Controller
{
    public function edit(Request $request): RedirectResponse|Response
    {
        if ($request->user()->isAdmin() || $request->user()->isStaff()) {
            return redirect()->route('profile.edit');
        }

        $customer = $request->user()->ensureCustomerRecord();

        return Inertia::render('settings/RentalProfile', [
            'customer' => $this->customerProps($customer),
        ]);
    }

    public function update(RentalProfileUpdateRequest $request): RedirectResponse
    {
        if ($request->user()->isAdmin() || $request->user()->isStaff()) {
            return redirect()->route('profile.edit');
        }

        $customer = $request->user()->ensureCustomerRecord();

        $customer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Rental profile updated.')]);

        return to_route('rental-profile.edit');
    }

    /**
     * @return array<string, string|null>
     */
    private function customerProps(Customer $customer): array
    {
        return [
            'driver_license_number' => $customer->driver_license_number,
            'license_expiry' => $customer->license_expiry?->format('Y-m-d'),
            'company_name' => $customer->company_name,
            'tax_number' => $customer->tax_number,
            'address' => $customer->address,
        ];
    }
}
