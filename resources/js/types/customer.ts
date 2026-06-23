export type RentalProfile = {
    driver_license_number: string | null;
    license_expiry: string | null;
    company_name: string | null;
    tax_number: string | null;
    address: string | null;
};

export type RentalProfileForm = {
    driver_license_number: string;
    license_expiry: string;
    company_name: string;
    tax_number: string;
    address: string;
};
