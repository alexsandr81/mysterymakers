<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'phone', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_moderator' => 'boolean',
            'admin_sections' => 'array',
        ];
    }

    /** Роль: admin (всё), moderator (товары и каталог, без цен и заказов), customer */
    public function role(): string
    {
        return $this->is_admin ? 'admin' : ($this->is_moderator ? 'moderator' : 'customer');
    }

    /** Можно ли открыть раздел админки: администратору — всё; модератору — выбранные разделы (NULL = все) */
    public function canAccess(string $section): bool
    {
        if ($this->is_admin) {
            return true;
        }

        return $this->is_moderator && ($this->admin_sections === null || in_array($section, $this->admin_sections, true));
    }

    public function isStaff(): bool
    {
        return $this->is_admin || $this->is_moderator;
    }

    /** Письмо для сброса пароля — на языке сайта */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    private ?array $favoriteIdsCache = null;

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'favorites')->withTimestamps();
    }

    /** ID избранных товаров (один запрос на всю страницу) */
    public function favoriteIds(): array
    {
        return $this->favoriteIdsCache ??= $this->favorites()->pluck('products.id')->all();
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
