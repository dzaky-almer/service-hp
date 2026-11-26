<?php

namespace App\Notifications;

use App\Models\Pengguna;
use App\Models\UndanganAkun;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UndanganAktivasi extends Notification
{
    /** Tautan aktivasi; dirakit di sini supaya token asli tidak ikut tersimpan. */
    public readonly string $tautan;

    public function __construct(public Pengguna $pengguna, public string $token)
    {
        $this->tautan = route('aktivasi.create', ['token' => $token]);
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Undangan akun '.config('app.name'))
            ->greeting('Halo '.$this->pengguna->nama.',')
            ->line('Anda diundang sebagai '.$this->pengguna->peran.' di '.config('app.name').'.')
            ->line('Klik tombol di bawah untuk menetapkan password dan mengaktifkan akun Anda.')
            ->action('Aktifkan akun', $this->tautan)
            ->line('Tautan ini hanya bisa dipakai sekali dan berlaku '.UndanganAkun::MASA_BERLAKU_JAM.' jam.');
    }
}
