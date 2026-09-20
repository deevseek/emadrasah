<?php

declare(strict_types=1);
namespace App\Models\Pmbm;
use Illuminate\Database\Eloquent\Model;
class PmbmDocumentRequirement extends Model { protected $guarded=[]; protected function casts(): array { return ['is_required'=>'boolean','is_active'=>'boolean']; } }
