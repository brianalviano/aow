<?php

namespace App\Models;

use App\Enums\{JournalTemplateLinePosition, JournalTemplateMappingKey, JournalAmountSource};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JournalTemplateLine extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'journal_template_id',
        'position',
        'account_id',
        'mapping_key',
        'amount_source',
        'custom_formula',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => JournalTemplateLinePosition::class,
            'mapping_key' => JournalTemplateMappingKey::class,
            'amount_source' => JournalAmountSource::class,
        ];
    }

    public function journalTemplate(): BelongsTo
    {
        return $this->belongsTo(JournalTemplate::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
