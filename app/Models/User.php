<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'level',
        'foto',
        'id_section',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function scopeIsNotAdmin($query)
    {
        return $query->where('level', '!=', 1);
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'id_section', 'id_section');
    }

    /**
     * The section this user checks out sales into (the "checkout point").
     */
    const CHECKOUT_SECTION = 'PROVISIONS';

    public function isAdmin(): bool
    {
        return (int) $this->level === 1;
    }

    public function sectionName(): ?string
    {
        return $this->section ? strtoupper(trim($this->section->nama_section)) : null;
    }

    /**
     * Provisions cashier: the checkout point that receives baskets from pickers.
     */
    public function isProvisions(): bool
    {
        return ! $this->isAdmin() && $this->sectionName() === self::CHECKOUT_SECTION;
    }

    /**
     * Picker (e.g. Pharmacy): a section-scoped cashier who can only build a
     * basket and forward it to Provisions, never check out.
     */
    public function isPicker(): bool
    {
        return ! $this->isAdmin()
            && ! is_null($this->id_section)
            && ! $this->isProvisions();
    }

    /**
     * Whether this user is limited to a single section's products.
     */
    public function isSectionScoped(): bool
    {
        return ! $this->isAdmin() && ! is_null($this->id_section);
    }
}
