<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\{Account, AccountMapping, CashBankAccount, AccountingPeriod, JournalTemplate, JournalTemplateLine, WarehouseAccount, Warehouse};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;


/**
 * Men-seed chart of accounts dan pemetaan akuntansi standar, sederhana.
 *
 * - Idempotent: menggunakan upsert/updateOrInsert agar aman diulang.
 * - Aman ketika tabel belum dibuat: setiap langkah cek keberadaan tabel.
 *
 * Akun yang disiapkan mencakup:
 * - Kas, Bank, Piutang Usaha, Persediaan, PPN Masukan
 * - Hutang Usaha, Akrual Penerimaan Barang, PPN Keluaran
 * - Laba Ditahan
 * - Penjualan, Diskon Penjualan (kontra revenue), HPP
 * - Biaya Pengiriman, Pembulatan
 * - Selisih Persediaan Naik/Turun
 *
 * Pemetaan disiapkan untuk kunci bisnis umum:
 * inventory, cogs, sales_revenue, sales_discount, sales_rounding, sales_shipping,
 * ppn_output, ppn_input, goods_receipt_accrual, accounts_receivable, accounts_payable.
 */
class AccountingSeeder extends Seeder
{
    /**
     * Entry point seeding akuntansi.
     *
     * @return void
     */
    public function run(): void
    {
        $this->seedAccountingPeriods();
        $this->seedAccounts();
        $this->seedAccountMappings();
        $this->seedCashBankAccounts();
        $this->seedJournalTemplates();
        $this->seedWarehouseAccounts();
    }

    /**
     * Seed satu periode akuntansi sederhana: bulan berjalan berstatus open.
     *
     * @return void
     */
    protected function seedAccountingPeriods(): void
    {
        if (!Schema::hasTable('accounting_periods')) {
            return;
        }

        $start = now()->startOfMonth()->toDateString();
        $end = now()->endOfMonth()->toDateString();
        $code = now()->format('Y-m');
        $name = 'Periode ' . now()->format('Y-m');
        $nowTs = now();

        AccountingPeriod::query()->updateOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'start_date' => $start,
                'end_date' => $end,
                'status' => 'open',
                'closed_at' => null,
                'closed_by_id' => null,
                'updated_at' => $nowTs,
            ]
        );
    }

    /**
     * Seed chart of accounts minimal/standar.
     *
     * @return void
     */
    protected function seedAccounts(): void
    {
        if (!Schema::hasTable('accounts')) {
            return;
        }

        $now = now();
        $rows = [
            // Asset
            ['code' => '1000', 'name' => 'Kas', 'type' => 'asset', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1100', 'name' => 'Bank', 'type' => 'asset', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1200', 'name' => 'Piutang Usaha', 'type' => 'asset', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1300', 'name' => 'Persediaan Barang', 'type' => 'asset', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '1400', 'name' => 'PPN Masukan', 'type' => 'asset', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Liability
            ['code' => '2000', 'name' => 'Hutang Usaha', 'type' => 'liability', 'normal_side' => 'credit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2100', 'name' => 'Akrual Penerimaan Barang', 'type' => 'liability', 'normal_side' => 'credit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '2200', 'name' => 'PPN Keluaran', 'type' => 'liability', 'normal_side' => 'credit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Equity
            ['code' => '3000', 'name' => 'Laba Ditahan', 'type' => 'equity', 'normal_side' => 'credit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Revenue
            ['code' => '4000', 'name' => 'Penjualan', 'type' => 'revenue', 'normal_side' => 'credit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            // Contra revenue ditandai normal_side debit
            ['code' => '4100', 'name' => 'Diskon Penjualan', 'type' => 'revenue', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '4300', 'name' => 'Pendapatan Selisih Persediaan', 'type' => 'revenue', 'normal_side' => 'credit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],

            // Expense
            ['code' => '5000', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5100', 'name' => 'Biaya Pengiriman', 'type' => 'expense', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5200', 'name' => 'Pembulatan', 'type' => 'expense', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['code' => '5400', 'name' => 'Kerugian Selisih Persediaan', 'type' => 'expense', 'normal_side' => 'debit', 'parent_account_id' => null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($rows as $row) {
            Account::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'normal_side' => $row['normal_side'],
                    'parent_account_id' => $row['parent_account_id'],
                    'is_active' => $row['is_active'],
                ],
            );
        }
    }

    /**
     * Seed pemetaan kunci bisnis ke akun standar.
     *
     * @return void
     */
    protected function seedAccountMappings(): void
    {
        if (!Schema::hasTable('account_mappings') || !Schema::hasTable('accounts')) {
            return;
        }

        $accountIdsByCode = Account::query()->pluck('id', 'code');
        $now = now();
        $rows = [
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'inventory',              'account_id' => $accountIdsByCode['1300'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'cogs',                   'account_id' => $accountIdsByCode['5000'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'sales_revenue',          'account_id' => $accountIdsByCode['4000'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'sales_discount',         'account_id' => $accountIdsByCode['4100'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'sales_rounding',         'account_id' => $accountIdsByCode['5200'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'sales_shipping',         'account_id' => $accountIdsByCode['5100'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'ppn_output',             'account_id' => $accountIdsByCode['2200'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'ppn_input',              'account_id' => $accountIdsByCode['1400'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'goods_receipt_accrual',  'account_id' => $accountIdsByCode['2100'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'accounts_receivable',    'account_id' => $accountIdsByCode['1200'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['scope' => 'global', 'warehouse_id' => null, 'key' => 'accounts_payable',       'account_id' => $accountIdsByCode['2000'] ?? null, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($rows as $row) {
            AccountMapping::query()->updateOrCreate(
                ['scope' => $row['scope'], 'warehouse_id' => $row['warehouse_id'], 'key' => $row['key']],
                ['account_id' => $row['account_id'], 'is_active' => $row['is_active']],
            );
        }
    }

    /**
     * Seed rekening kas/bank standar.
     *
     * @return void
     */
    protected function seedCashBankAccounts(): void
    {
        if (!Schema::hasTable('cash_bank_accounts') || !Schema::hasTable('accounts')) {
            return;
        }

        $accountIdsByCode = Account::query()->pluck('id', 'code');
        $now = now();
        $rows = [
            [
                'type' => 'cash',
                'name' => 'Kas Utama',
                'code' => 'CSH-001',
                'bank_name' => null,
                'account_number' => null,
                'account_holder_name' => null,
                'currency' => 'idr',
                'account_id' => $accountIdsByCode['1000'] ?? null,
                'opening_balance' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'type' => 'bank',
                'name' => 'Bank Utama',
                'code' => 'BNK-001',
                'bank_name' => null,
                'account_number' => null,
                'account_holder_name' => null,
                'currency' => 'idr',
                'account_id' => $accountIdsByCode['1100'] ?? null,
                'opening_balance' => 0,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($rows as $row) {
            CashBankAccount::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'type' => $row['type'],
                    'name' => $row['name'],
                    'bank_name' => $row['bank_name'],
                    'account_number' => $row['account_number'],
                    'account_holder_name' => $row['account_holder_name'],
                    'currency' => $row['currency'],
                    'account_id' => $row['account_id'],
                    'opening_balance' => $row['opening_balance'],
                    'is_active' => $row['is_active'],
                ],
            );
        }
    }

    /**
     * Seed template jurnal minimal untuk penjualan, penerimaan barang, tagihan supplier.
     *
     * @return void
     */
    protected function seedJournalTemplates(): void
    {
        if (!Schema::hasTable('journal_templates') || !Schema::hasTable('journal_template_lines')) {
            return;
        }

        $now = now();
        // Template: Penjualan
        $salesTemplate = [
            'code' => 'SALES',
            'name' => 'Penjualan',
            'module' => 'sales',
            'is_active' => true,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        JournalTemplate::query()->updateOrCreate(
            ['code' => $salesTemplate['code']],
            [
                'name' => $salesTemplate['name'],
                'module' => $salesTemplate['module'],
                'is_active' => true,
                'notes' => null,
                'updated_at' => $now,
            ]
        );
        $salesTemplateId = JournalTemplate::query()->where('code', $salesTemplate['code'])->value('id');
        if ($salesTemplateId) {
            $salesLines = [
                // Debit Piutang (grand_total)
                [
                    'journal_template_id' => $salesTemplateId,
                    'position' => 'debit',
                    'account_id' => null,
                    'mapping_key' => 'accounts_receivable',
                    'amount_source' => 'grand_total',
                    'custom_formula' => null,
                ],
                // Kredit Penjualan (total setelah diskon)
                [
                    'journal_template_id' => $salesTemplateId,
                    'position' => 'credit',
                    'account_id' => null,
                    'mapping_key' => 'sales_revenue',
                    'amount_source' => 'custom',
                    'custom_formula' => 'grand_total - vat_amount',
                ],
                // Kredit PPN Keluaran (vat_amount)
                [
                    'journal_template_id' => $salesTemplateId,
                    'position' => 'credit',
                    'account_id' => null,
                    'mapping_key' => 'ppn_output',
                    'amount_source' => 'vat_amount',
                    'custom_formula' => null,
                ],
            ];
            JournalTemplateLine::query()->where('journal_template_id', $salesTemplateId)->delete();
            foreach ($salesLines as $line) {
                JournalTemplateLine::create($line);
            }
        }

        // Template: Penerimaan Barang (Goods Receipt)
        $grTemplate = [
            'code' => 'GRN',
            'name' => 'Penerimaan Barang',
            'module' => 'goods_receipt',
            'is_active' => true,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        JournalTemplate::query()->updateOrCreate(
            ['code' => $grTemplate['code']],
            [
                'name' => $grTemplate['name'],
                'module' => $grTemplate['module'],
                'is_active' => true,
                'notes' => null,
                'updated_at' => $now,
            ]
        );
        $grTemplateId = JournalTemplate::query()->where('code', $grTemplate['code'])->value('id');
        if ($grTemplateId) {
            $grLines = [
                // Debit Persediaan (cost_amount)
                [
                    'journal_template_id' => $grTemplateId,
                    'position' => 'debit',
                    'account_id' => null,
                    'mapping_key' => 'inventory',
                    'amount_source' => 'cost_amount',
                    'custom_formula' => null,
                ],
                // Kredit Akrual Penerimaan Barang (cost_amount)
                [
                    'journal_template_id' => $grTemplateId,
                    'position' => 'credit',
                    'account_id' => null,
                    'mapping_key' => 'goods_receipt_accrual',
                    'amount_source' => 'cost_amount',
                    'custom_formula' => null,
                ],
            ];
            JournalTemplateLine::query()->where('journal_template_id', $grTemplateId)->delete();
            foreach ($grLines as $line) {
                JournalTemplateLine::create($line);
            }
        }

        // Template: Tagihan Supplier (Supplier Bill)
        $billTemplate = [
            'code' => 'BILL',
            'name' => 'Tagihan Supplier',
            'module' => 'purchase',
            'is_active' => true,
            'notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        JournalTemplate::query()->updateOrCreate(
            ['code' => $billTemplate['code']],
            [
                'name' => $billTemplate['name'],
                'module' => $billTemplate['module'],
                'is_active' => true,
                'notes' => null,
                'updated_at' => $now,
            ]
        );
        $billTemplateId = JournalTemplate::query()->where('code', $billTemplate['code'])->value('id');
        if ($billTemplateId) {
            $billLines = [
                // Debit Akrual Penerimaan Barang (subtotal/cost)
                [
                    'journal_template_id' => $billTemplateId,
                    'position' => 'debit',
                    'account_id' => null,
                    'mapping_key' => 'goods_receipt_accrual',
                    'amount_source' => 'subtotal',
                    'custom_formula' => null,
                ],
                // Debit PPN Masukan (vat_amount)
                [
                    'journal_template_id' => $billTemplateId,
                    'position' => 'debit',
                    'account_id' => null,
                    'mapping_key' => 'ppn_input',
                    'amount_source' => 'vat_amount',
                    'custom_formula' => null,
                ],
                // Kredit Hutang Usaha (grand_total)
                [
                    'journal_template_id' => $billTemplateId,
                    'position' => 'credit',
                    'account_id' => null,
                    'mapping_key' => 'accounts_payable',
                    'amount_source' => 'grand_total',
                    'custom_formula' => null,
                ],
            ];
            JournalTemplateLine::query()->where('journal_template_id', $billTemplateId)->delete();
            foreach ($billLines as $line) {
                JournalTemplateLine::create($line);
            }
        }
    }

    /**
     * Seed akun gudang per baris gudang jika tersedia.
     *
     * @return void
     */
    protected function seedWarehouseAccounts(): void
    {
        if (!Schema::hasTable('warehouse_accounts') || !Schema::hasTable('warehouses') || !Schema::hasTable('accounts')) {
            return;
        }

        $inventoryId = Account::query()->where('code', '1300')->value('id');
        $cogsId = Account::query()->where('code', '5000')->value('id');
        $adjIncId = Account::query()->where('code', '4300')->value('id');
        $adjDecId = Account::query()->where('code', '5400')->value('id');
        $now = now();

        $warehouses = Warehouse::query()->select('id')->get();
        if ($warehouses->isEmpty()) {
            return;
        }

        foreach ($warehouses as $wh) {
            WarehouseAccount::query()->updateOrCreate(
                ['warehouse_id' => $wh->id],
                [
                    'inventory_account_id' => $inventoryId,
                    'cogs_account_id' => $cogsId,
                    'adjustment_increase_account_id' => $adjIncId,
                    'adjustment_decrease_account_id' => $adjDecId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }
}
