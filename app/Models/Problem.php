<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'problem_it';

    protected $fillable = [
        'no_wo',
        'jenis_permintaan',
        'user_id',
        'keperluan',
        'office',
        'waktu_request',
        'waktu_mulai',
        'waktu_selesai',
        'waktu_pengerjaan',
        'eksekutor',
        'keterangan',
        'catatan',
        'email_inl',
        'remark',
        'nrk',
        'hp',
        'info',
        'status',
        'created_at',
        'updated_at',
    ];

}
