<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory; // EZ FONTOS

    protected $fillable = ['name', 'email', 'birth_date'];
}

?>
