<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** Log mentah webhook pembayaran (fitur 42, SECURITY.md §5). Hanya INSERT. */
#[Fillable(['penyedia', 'id_transaksi_eksternal', 'payload', 'tanda_tangan_valid', 'waktu'])]
class WebhookLog extends Model
{
    protected $table = 'webhook_log';

    protected $primaryKey = 'id_log';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'tanda_tangan_valid' => 'boolean',
            'waktu' => 'datetime',
        ];
    }
}
