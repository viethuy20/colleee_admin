<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LineAccount extends Model
{
    protected $fillable = ['user_id', 'line_id', 'token', 'referenceNo', 'created_at', 'updated_at'];
}
