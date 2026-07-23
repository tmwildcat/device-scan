<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\LineWatt\Access\EntitlementChecker;
use App\LineWatt\Access\LineWattRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $role
 * @property string|null $plan_code
 * @property string|null $subscription_status
 * @property string $preferred_locale
 * @property int|null $manufacturer_company_id
 * @property string|null $manufacturer_role
 * @property array<string,bool>|null $entitlement_overrides
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'email',
    'password',
    'role',
    'plan_code',
    'subscription_status',
    'preferred_locale',
    'manufacturer_company_id',
    'manufacturer_role',
    'entitlement_overrides',
    'email_verified_at',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'entitlement_overrides' => 'array',
        ];
    }

    public function roleLabel(): string
    {
        return LineWattRole::label($this->role);
    }

    /**
     * @return BelongsTo<ManufacturerCompany,self>
     */
    public function manufacturerCompany(): BelongsTo
    {
        return $this->belongsTo(ManufacturerCompany::class);
    }

    public function hasLineWattRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasEntitlement(string $entitlement): bool
    {
        return app(EntitlementChecker::class)->has($this, $entitlement);
    }

    public function canAccessCentralLibrary(): bool
    {
        return app(EntitlementChecker::class)->canAccessCentralLibrary($this);
    }

    public function canAccessMyLibrary(): bool
    {
        return app(EntitlementChecker::class)->canAccessMyLibrary($this);
    }

    public function canAccessPartnerPortal(): bool
    {
        return app(EntitlementChecker::class)->canAccessPartnerPortal($this);
    }

    public function hasLegalPermission(string $permission): bool
    {
        if ($this->role === LineWattRole::SUPER_ADMIN) {
            return true;
        }

        return in_array($permission, config('legal-governance.permissions', []), true)
            && in_array($permission, config("legal-governance.role_permissions.{$this->role}", []), true);
    }

    /**
     * @return HasMany<Notification>
     */
    public function lineWattNotifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}
