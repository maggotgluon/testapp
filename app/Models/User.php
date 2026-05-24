<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'username', 'email', 'phone', 'role', 'provider', 'provider_id', 'avatar', 'password', 'line_friend_status', 'line_followed_at', 'line_blocked_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasPushSubscriptions, Notifiable;

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
            'line_followed_at' => 'datetime',
            'line_blocked_at' => 'datetime',
        ];
    }

    public function orders()
    {
        return $this->hasMany(TicketOrder::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class)->withTimestamps();
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'event_admin', 'gate_scanner'], true);
    }

    public function canManageEvent(Event|int $event): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        $eventId = $event instanceof Event ? $event->id : $event;

        return in_array($this->role, ['event_admin', 'gate_scanner'], true)
            && $this->assignedEvents()->whereKey($eventId)->exists();
    }
}
