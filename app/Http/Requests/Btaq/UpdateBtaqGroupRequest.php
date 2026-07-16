<?php

declare(strict_types=1);

namespace App\Http\Requests\Btaq;

final class UpdateBtaqGroupRequest extends StoreBtaqGroupRequest
{
    public function rules(): array
    {
        return $this->baseRules($this->route('btaqGroup')?->id);
    }
}
