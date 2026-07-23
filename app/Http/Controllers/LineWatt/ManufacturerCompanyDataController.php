<?php

namespace App\Http\Controllers\LineWatt;

use App\Http\Controllers\Controller;
use App\LineWatt\Access\LineWattRole;
use App\LineWatt\Storage\LineWattStorage;
use App\Models\ManufacturerCompany;
use App\Models\ManufacturerCountryContact;
use App\Models\ManufacturerDistributionCountry;
use App\Models\ManufacturerFactoryLocation;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Response as DownloadResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ManufacturerCompanyDataController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('legal.acceptance:manufacturer.portal.access')];
    }

    public function storeLogo(Request $request, LineWattStorage $storage): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($company && $this->canManage($request), 403);

        $data = $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);

        $file = $data['logo'];
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'png');
        $path = $storage->basePath('manufacturer-companies', $company->uuid, 'profile', 'logo-'.now()->format('YmdHis').'.'.$extension);
        $metadata = $company->metadata ?? [];

        if (! empty($metadata['logo_path'])) {
            $storage->delete((string) $metadata['logo_path']);
        }

        $storage->put($path, file_get_contents($file->getRealPath()), [
            'visibility' => 'private',
            'ContentType' => $file->getMimeType(),
        ]);

        $metadata['logo_disk'] = $storage->diskName();
        $metadata['logo_path'] = $path;
        $metadata['logo_original_filename'] = $file->getClientOriginalName();
        $metadata['logo_mime_type'] = $file->getMimeType();
        $metadata['logo_size_bytes'] = $file->getSize();
        $metadata['logo_updated_at'] = now()->toDateTimeString();

        $company->forceFill(['metadata' => $metadata])->save();

        return back()->with('success', 'Company logo updated.');
    }

    public function destroyLogo(Request $request, LineWattStorage $storage): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($company && $this->canManage($request), 403);

        $metadata = $company->metadata ?? [];

        if (! empty($metadata['logo_path'])) {
            $storage->delete((string) $metadata['logo_path']);
        }

        unset(
            $metadata['logo_disk'],
            $metadata['logo_path'],
            $metadata['logo_original_filename'],
            $metadata['logo_mime_type'],
            $metadata['logo_size_bytes'],
            $metadata['logo_updated_at']
        );

        $company->forceFill(['metadata' => $metadata])->save();

        return back()->with('success', 'Company logo removed.');
    }

    public function logo(Request $request, LineWattStorage $storage): SymfonyResponse
    {
        $company = $this->company($request);
        abort_unless($company, 404);

        $metadata = $company->metadata ?? [];
        $path = $metadata['logo_path'] ?? null;
        abort_unless(is_string($path) && $path !== '' && $storage->exists($path), 404);

        return response($storage->get($path), 200, [
            'Content-Type' => (string) ($metadata['logo_mime_type'] ?? 'image/png'),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function qr(Request $request, string $format): SymfonyResponse
    {
        $company = $this->company($request);
        $slug = $company?->slug ?? str($company?->name ?? 'manufacturer')->slug()->toString();
        $url = route('manufacturers.show', ['manufacturer' => $slug]);

        $writer = $format === 'svg' ? new SvgWriter : new PngWriter;
        $result = (new Builder(
            writer: $writer,
            data: $url,
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 640,
            margin: 24,
            foregroundColor: new Color(15, 23, 42),
            backgroundColor: new Color(255, 255, 255),
        ))->build();

        $mime = $format === 'svg' ? 'image/svg+xml' : 'image/png';
        $filename = 'linewatt-'.$slug.'-qr.'.$format;

        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return DownloadResponse::make($result->getString(), 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition.'; filename="'.$filename.'"',
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function factories(Request $request): Response
    {
        $company = $this->company($request);
        $rows = $company && Schema::hasTable('manufacturer_factory_locations')
            ? $company->factoryLocations()->latest()->paginate(15)->through(fn (ManufacturerFactoryLocation $location): array => $this->factoryPayload($location))
            : null;

        return Inertia::render('LineWatt/ManufacturerFactoryLocations', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'permissionMessage' => $this->permissionMessage($request),
            'locations' => $rows,
        ]);
    }

    public function createFactory(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerFactoryLocationForm', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'mode' => 'create',
            'location' => $this->emptyFactoryPayload(),
        ]);
    }

    public function storeFactory(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($company && $this->canManage($request), 403);

        $data = $this->validateFactory($request);
        $company->factoryLocations()->create($data);

        return redirect()
            ->route('admin.manufacturer.company.factories')
            ->with('success', 'Factory location added.');
    }

    public function editFactory(Request $request, ManufacturerFactoryLocation $factory): Response
    {
        $this->authorizeFactory($request, $factory);

        return Inertia::render('LineWatt/ManufacturerFactoryLocationForm', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'mode' => 'edit',
            'location' => $this->factoryPayload($factory),
        ]);
    }

    public function updateFactory(Request $request, ManufacturerFactoryLocation $factory): RedirectResponse
    {
        $this->authorizeFactory($request, $factory);
        abort_unless($this->canManage($request), 403);

        $factory->update($this->validateFactory($request));

        return redirect()
            ->route('admin.manufacturer.company.factories')
            ->with('success', 'Factory location updated.');
    }

    public function destroyFactory(Request $request, ManufacturerFactoryLocation $factory): RedirectResponse
    {
        $this->authorizeFactory($request, $factory);
        abort_unless($this->canManage($request), 403);

        $factory->delete();

        return redirect()
            ->route('admin.manufacturer.company.factories')
            ->with('success', 'Factory location removed.');
    }

    public function distributionCountries(Request $request): Response
    {
        $company = $this->company($request);
        $rows = $company && Schema::hasTable('manufacturer_distribution_countries')
            ? $company->distributionCountries()->latest()->paginate(15)->through(fn (ManufacturerDistributionCountry $country): array => $this->distributionPayload($country))
            : null;

        return Inertia::render('LineWatt/ManufacturerDistributionCountries', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'permissionMessage' => $this->permissionMessage($request),
            'countries' => $rows,
        ]);
    }

    public function createDistributionCountry(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerDistributionCountryForm', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'mode' => 'create',
            'country' => $this->emptyDistributionPayload(),
        ]);
    }

    public function storeDistributionCountry(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($company && $this->canManage($request), 403);

        $company->distributionCountries()->create($this->validateDistribution($request));

        return redirect()
            ->route('admin.manufacturer.company.distribution-countries')
            ->with('success', 'Distribution country added.');
    }

    public function editDistributionCountry(Request $request, ManufacturerDistributionCountry $country): Response
    {
        $this->authorizeDistributionCountry($request, $country);

        return Inertia::render('LineWatt/ManufacturerDistributionCountryForm', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'mode' => 'edit',
            'country' => $this->distributionPayload($country),
        ]);
    }

    public function updateDistributionCountry(Request $request, ManufacturerDistributionCountry $country): RedirectResponse
    {
        $this->authorizeDistributionCountry($request, $country);
        abort_unless($this->canManage($request), 403);

        $country->update($this->validateDistribution($request));

        return redirect()
            ->route('admin.manufacturer.company.distribution-countries')
            ->with('success', 'Distribution country updated.');
    }

    public function destroyDistributionCountry(Request $request, ManufacturerDistributionCountry $country): RedirectResponse
    {
        $this->authorizeDistributionCountry($request, $country);
        abort_unless($this->canManage($request), 403);

        $country->delete();

        return redirect()
            ->route('admin.manufacturer.company.distribution-countries')
            ->with('success', 'Distribution country removed.');
    }

    public function countryContacts(Request $request): Response
    {
        $company = $this->company($request);
        $rows = $company && Schema::hasTable('manufacturer_country_contacts')
            ? $company->countryContacts()->latest()->paginate(15)->through(fn (ManufacturerCountryContact $contact): array => $this->countryContactPayload($contact))
            : null;

        return Inertia::render('LineWatt/ManufacturerCountryContacts', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'permissionMessage' => $this->permissionMessage($request),
            'contacts' => $rows,
        ]);
    }

    public function createCountryContact(Request $request): Response
    {
        return Inertia::render('LineWatt/ManufacturerCountryContactForm', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'mode' => 'create',
            'contact' => $this->emptyCountryContactPayload(),
        ]);
    }

    public function storeCountryContact(Request $request): RedirectResponse
    {
        $company = $this->company($request);
        abort_unless($company && $this->canManage($request), 403);

        $company->countryContacts()->create($this->validateCountryContact($request));

        return redirect()
            ->route('admin.manufacturer.country-contacts')
            ->with('success', 'Country contact added.');
    }

    public function editCountryContact(Request $request, ManufacturerCountryContact $contact): Response
    {
        $this->authorizeCountryContact($request, $contact);

        return Inertia::render('LineWatt/ManufacturerCountryContactForm', [
            'company' => $this->companyPayload($request),
            'canManage' => $this->canManage($request),
            'mode' => 'edit',
            'contact' => $this->countryContactPayload($contact),
        ]);
    }

    public function updateCountryContact(Request $request, ManufacturerCountryContact $contact): RedirectResponse
    {
        $this->authorizeCountryContact($request, $contact);
        abort_unless($this->canManage($request), 403);

        $contact->update($this->validateCountryContact($request));

        return redirect()
            ->route('admin.manufacturer.country-contacts')
            ->with('success', 'Country contact updated.');
    }

    public function destroyCountryContact(Request $request, ManufacturerCountryContact $contact): RedirectResponse
    {
        $this->authorizeCountryContact($request, $contact);
        abort_unless($this->canManage($request), 403);

        $contact->delete();

        return redirect()
            ->route('admin.manufacturer.country-contacts')
            ->with('success', 'Country contact removed.');
    }

    /**
     * @return array<string,string|null>
     */
    private function validateFactory(Request $request): array
    {
        return $request->validate([
            'factory_name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'product_types' => ['nullable', 'string', 'max:255'],
            'production_capacity' => ['nullable', 'string', 'max:255'],
            'certifications' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'planned', 'inactive', 'closed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string,string|null>
     */
    private function validateDistribution(Request $request): array
    {
        return $request->validate([
            'country' => ['required', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'availability_status' => ['required', Rule::in(['available', 'planned', 'restricted', 'discontinued'])],
            'channel_model' => ['nullable', 'string', 'max:255'],
            'distributor_name' => ['nullable', 'string', 'max:255'],
            'sales_contact' => ['nullable', 'string', 'max:255'],
            'service_contact' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    /**
     * @return array<string,string|null>
     */
    private function validateCountryContact(Request $request): array
    {
        return $request->validate([
            'country' => ['required', 'string', 'max:255'],
            'contact_type' => ['required', Rule::in(['general', 'sales', 'technical', 'warranty', 'service'])],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'pending'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }

    private function authorizeFactory(Request $request, ManufacturerFactoryLocation $factory): void
    {
        $company = $this->company($request);
        $isPlatformOperator = $this->isPlatformOperator($request);

        abort_unless($isPlatformOperator || ($company && $factory->manufacturer_company_id === $company->id), 404);
    }

    private function authorizeDistributionCountry(Request $request, ManufacturerDistributionCountry $country): void
    {
        $company = $this->company($request);
        $isPlatformOperator = $this->isPlatformOperator($request);

        abort_unless($isPlatformOperator || ($company && $country->manufacturer_company_id === $company->id), 404);
    }

    private function authorizeCountryContact(Request $request, ManufacturerCountryContact $contact): void
    {
        $company = $this->company($request);
        $isPlatformOperator = $this->isPlatformOperator($request);

        abort_unless($isPlatformOperator || ($company && $contact->manufacturer_company_id === $company->id), 404);
    }

    /**
     * @return array<string,mixed>
     */
    private function factoryPayload(ManufacturerFactoryLocation $location): array
    {
        return [
            'id' => $location->id,
            'uuid' => $location->uuid,
            'factory_name' => $location->factory_name,
            'country' => $location->country,
            'state' => $location->state,
            'city' => $location->city,
            'address' => $location->address,
            'product_types' => $location->product_types,
            'production_capacity' => $location->production_capacity,
            'certifications' => $location->certifications,
            'status' => $location->status,
            'notes' => $location->notes,
            'updated' => $location->updated_at?->toDateString(),
            'edit_href' => route('admin.manufacturer.company.factories.edit', ['factory' => $location->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function distributionPayload(ManufacturerDistributionCountry $country): array
    {
        return [
            'id' => $country->id,
            'uuid' => $country->uuid,
            'country' => $country->country,
            'region' => $country->region,
            'availability_status' => $country->availability_status,
            'channel_model' => $country->channel_model,
            'distributor_name' => $country->distributor_name,
            'sales_contact' => $country->sales_contact,
            'service_contact' => $country->service_contact,
            'notes' => $country->notes,
            'updated' => $country->updated_at?->toDateString(),
            'edit_href' => route('admin.manufacturer.company.distribution-countries.edit', ['country' => $country->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function countryContactPayload(ManufacturerCountryContact $contact): array
    {
        return [
            'id' => $contact->id,
            'uuid' => $contact->uuid,
            'country' => $contact->country,
            'contact_type' => $contact->contact_type,
            'contact_name' => $contact->contact_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'website' => $contact->website,
            'region' => $contact->region,
            'status' => $contact->status,
            'notes' => $contact->notes,
            'updated' => $contact->updated_at?->toDateString(),
            'edit_href' => route('admin.manufacturer.country-contacts.edit', ['contact' => $contact->id]),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyFactoryPayload(): array
    {
        return [
            'id' => null,
            'factory_name' => '',
            'country' => '',
            'state' => '',
            'city' => '',
            'address' => '',
            'product_types' => '',
            'production_capacity' => '',
            'certifications' => '',
            'status' => 'active',
            'notes' => '',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyDistributionPayload(): array
    {
        return [
            'id' => null,
            'country' => '',
            'region' => '',
            'availability_status' => 'available',
            'channel_model' => '',
            'distributor_name' => '',
            'sales_contact' => '',
            'service_contact' => '',
            'notes' => '',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function emptyCountryContactPayload(): array
    {
        return [
            'id' => null,
            'country' => '',
            'contact_type' => 'general',
            'contact_name' => '',
            'email' => '',
            'phone' => '',
            'website' => '',
            'region' => '',
            'status' => 'active',
            'notes' => '',
        ];
    }

    private function company(Request $request): ?ManufacturerCompany
    {
        return $request->user()?->manufacturerCompany;
    }

    private function canManage(Request $request): bool
    {
        $user = $request->user();

        return $this->isPlatformOperator($request) || $user?->manufacturer_role === 'manufacturer_admin';
    }

    private function isPlatformOperator(Request $request): bool
    {
        $user = $request->user();

        return $user && in_array($user->role, [LineWattRole::SUPER_ADMIN, LineWattRole::ADMIN], true);
    }

    private function permissionMessage(Request $request): ?string
    {
        return $this->canManage($request)
            ? null
            : 'Manufacturer Users can view company master data but cannot create, edit or delete it.';
    }

    /**
     * @return array<string,mixed>
     */
    private function companyPayload(Request $request): array
    {
        $user = $request->user();
        $company = $user?->manufacturerCompany;
        $plan = $company?->plan_code ?? 'pro';
        $isPlatformOperator = $this->isPlatformOperator($request);

        return [
            'name' => $company?->name ?? 'All Manufacturers',
            'plan_code' => $plan,
            'plan_label' => match ($plan) {
                'enterprise' => 'Enterprise',
                default => 'Pro',
            },
            'manufacturer_role_label' => match ($user?->manufacturer_role) {
                'manufacturer_admin' => 'Manufacturer Admin',
                'manufacturer_user' => 'Manufacturer User',
                default => $isPlatformOperator ? 'Platform Admin' : 'Manufacturer User',
            },
            'can_upgrade' => $user?->manufacturer_role === 'manufacturer_admin' && $plan === 'pro',
            'upgrade_message' => $user?->manufacturer_role === 'manufacturer_user'
                ? 'Please contact your Manufacturer Administrator to upgrade your subscription.'
                : null,
        ];
    }
}
