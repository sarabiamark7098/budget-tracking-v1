<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\FinancialGoal;
use App\Models\FinancialPlan;
use App\Models\Income;
use App\Models\InsurancePlan;
use App\Models\InsurancePayment;
use App\Models\Investment;
use App\Models\MP2Plan;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BudgetTrackingSeeder extends Seeder
{
    private User $user;

    public function run(): void
    {
        $this->user = User::firstOrCreate(
            ['email' => 'mark.sarabia@example.com'],
            [
                'name'     => 'Mark Sarabia',
                'password' => Hash::make('password'),
            ]
        );

        $this->seedIncomes();
        $this->seedExpenses();
        $this->seedPurchases();
        $this->seedInvestments();
        $this->seedStocks();
        $this->seedDebts();
        $this->seedInsurance();
        $this->seedMP2Plans();
        $this->seedFinancialPlans();
        $this->seedBudgets();
    }

    // ──────────────────────────────────────────
    // INCOMES  (Cash In sheet — cash-in rows only)
    // ──────────────────────────────────────────
    private function seedIncomes(): void
    {
        $otherIncome = Category::where('name', 'Other Income')->where('type', 'income')->first();

        // NOTE: 'source' must be one of the valid ENUM values:
        //   Compensation Income | Business Income | Passive Income | Property Gains | Other Sources
        // All cash-in / bank-transfer entries are classified as 'Other Sources'.
        $rows = [
            ['title' => 'LandBank #1477 4334 60 Cash-in', 'amount' => 12016.38, 'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'Landbank #1477 4212 76 Cash-in', 'amount' => 2000.00,   'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'BPI Cash-in',                    'amount' => 11110.06,  'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'Ownbank Cash-in',                'amount' => 48403.89,  'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'Samulco Cash-in',                'amount' => 1289.61,   'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'Gcash Mark Cash-in',             'amount' => 73.42,     'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'Gcash Gine Cash-in',             'amount' => 571.38,    'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'BPI Transfer from LandBank',     'amount' => 3000.00,   'source' => 'Other Sources', 'received_at' => '2025-02-15'],
            ['title' => 'Ownbank Transfer in',            'amount' => 3000.00,   'source' => 'Other Sources', 'received_at' => '2025-02-16'],
            ['title' => 'Gcash Gine Cash-in',             'amount' => 4000.00,   'source' => 'Other Sources', 'received_at' => '2025-02-16'],
            ['title' => 'LandBank #1477 4334 60 Cash-in', 'amount' => 26326.84,  'source' => 'Other Sources', 'received_at' => '2025-02-19'],
            ['title' => 'Ownbank Transfer in',            'amount' => 20000.00,  'source' => 'Other Sources', 'received_at' => '2025-02-21'],
            ['title' => 'BPI Cash-in from transfer',      'amount' => 3000.00,   'source' => 'Other Sources', 'received_at' => '2025-02-21'],
            ['title' => 'BPI from Ownbank',               'amount' => 71403.89,  'source' => 'Other Sources', 'received_at' => '2025-02-23'],
            ['title' => 'Gcash Mark Cash-in',             'amount' => 30.29,     'source' => 'Other Sources', 'received_at' => '2025-02-23'],
            ['title' => 'LandBank #1477 4334 60 Cash-in', 'amount' => 100.00,    'source' => 'Other Sources', 'received_at' => '2025-02-26'],
            ['title' => 'BPI from LandBank transfer',     'amount' => 1913.22,   'source' => 'Other Sources', 'received_at' => '2025-02-27'],
            ['title' => 'Landbank #1477 4212 76 Cash-in', 'amount' => 822.61,    'source' => 'Other Sources', 'received_at' => '2025-02-27'],
            ['title' => 'LandBank #1477 4334 60 Cash-in', 'amount' => 24518.50,  'source' => 'Other Sources', 'received_at' => '2025-02-27'],
        ];

        foreach ($rows as $row) {
            Income::firstOrCreate(
                ['user_id' => $this->user->id, 'title' => $row['title'], 'received_at' => $row['received_at']],
                array_merge($row, [
                    'user_id'      => $this->user->id,
                    'category_id'  => $otherIncome?->id,
                    'is_recurring' => false,
                ])
            );
        }
    }

    // ──────────────────────────────────────────
    // EXPENSES  (Rent, Water, Electricity)
    // ──────────────────────────────────────────
    private function seedExpenses(): void
    {
        $housing  = Category::where('name', 'Housing')->where('type', 'expense')->first();
        $bills    = Category::where('name', 'Bills')->where('type', 'expense')->first();

        $rentRows = [
            ['title' => 'Rent - Downpayment',  'amount' => 3000, 'spent_at' => '2025-02-11', 'description' => 'Downpayment'],
            ['title' => 'Rent - March',         'amount' => 3000, 'spent_at' => '2025-03-01', 'description' => 'Paid-March'],
            ['title' => 'Rent - April',         'amount' => 3000, 'spent_at' => '2025-04-16', 'description' => 'paid-April'],
            ['title' => 'Rent - May',           'amount' => 3000, 'spent_at' => '2025-05-29', 'description' => 'paid-May'],
            ['title' => 'Rent - June',          'amount' => 3000, 'spent_at' => '2025-06-30', 'description' => 'paid-June'],
            ['title' => 'Rent - July',          'amount' => 3000, 'spent_at' => '2025-07-31', 'description' => 'paid-July'],
            ['title' => 'Rent - August',        'amount' => 3000, 'spent_at' => '2025-08-29', 'description' => 'paid-August'],
            ['title' => 'Rent - September',     'amount' => 3000, 'spent_at' => '2025-09-30', 'description' => 'paid-September'],
            ['title' => 'Rent - October',       'amount' => 3000, 'spent_at' => '2025-10-30', 'description' => 'paid-October'],
            ['title' => 'Rent - November',      'amount' => 3000, 'spent_at' => '2025-11-30', 'description' => 'paid-November'],
            ['title' => 'Rent - December',      'amount' => 3000, 'spent_at' => '2025-12-30', 'description' => 'paid-December'],
            ['title' => 'Rent - January 2026',  'amount' => 3000, 'spent_at' => '2026-02-01', 'description' => 'paid-January'],
            ['title' => 'Rent - February 2026', 'amount' => 3000, 'spent_at' => '2026-03-01', 'description' => 'paid-February'],
        ];

        foreach ($rentRows as $row) {
            Expense::firstOrCreate(
                ['user_id' => $this->user->id, 'title' => $row['title'], 'spent_at' => $row['spent_at']],
                array_merge($row, ['user_id' => $this->user->id, 'category_id' => $housing?->id, 'is_recurring' => true, 'recurrence_interval' => 'monthly'])
            );
        }

        $waterRows = [
            ['title' => 'Water Bill - Jan-Feb',     'amount' => 158,  'spent_at' => '2025-03-15', 'description' => 'paid-Jan 30-February'],
            ['title' => 'Water Bill - Mar-Apr',     'amount' => 450,  'spent_at' => '2025-04-01', 'description' => 'paid-March-April'],
            ['title' => 'Water Bill - Apr-May',     'amount' => 339,  'spent_at' => '2025-05-05', 'description' => 'paid-April-May 15'],
            ['title' => 'Water Bill - May-Jun',     'amount' => 403,  'spent_at' => '2025-05-29', 'description' => 'paid-May 15-June 15'],
            ['title' => 'Water Bill - Aug',         'amount' => 452,  'spent_at' => '2025-08-03', 'description' => 'paid'],
            ['title' => 'Water Bill - Oct-Dec',     'amount' => 1526, 'spent_at' => '2026-02-01', 'description' => 'paid-Oct-Dec 2025'],
        ];

        foreach ($waterRows as $row) {
            Expense::firstOrCreate(
                ['user_id' => $this->user->id, 'title' => $row['title'], 'spent_at' => $row['spent_at']],
                array_merge($row, ['user_id' => $this->user->id, 'category_id' => $bills?->id, 'is_recurring' => false])
            );
        }

        $electricityRows = [
            ['title' => 'Electricity Front - Jan-Mar 2025',  'amount' => 65,   'spent_at' => '2025-03-23', 'description' => 'Front unit Jan 30–Mar 11'],
            ['title' => 'Electricity Front - Mar-May 2025',  'amount' => 312,  'spent_at' => '2025-05-19', 'description' => 'Front unit Mar 11–May 19'],
            ['title' => 'Electricity Front - May-Aug 2025',  'amount' => 468,  'spent_at' => '2025-08-03', 'description' => 'Front unit May 20–Aug 2'],
            ['title' => 'Electricity Front - Aug 2025-Jan 2026', 'amount' => 1124, 'spent_at' => '2026-03-13', 'description' => 'Front unit Aug 2–Jan 2'],
            ['title' => 'Electricity Back - Jan-Mar 2025',   'amount' => 117,  'spent_at' => '2025-03-23', 'description' => 'Back unit Jan 30–Mar 11'],
            ['title' => 'Electricity Back - Mar-May 2025',   'amount' => 416,  'spent_at' => '2025-05-19', 'description' => 'Back unit Mar 11–May 19'],
            ['title' => 'Electricity Back - May-Aug 2025',   'amount' => 533,  'spent_at' => '2025-08-03', 'description' => 'Back unit May 20–Aug 2'],
            ['title' => 'Electricity Back - Aug 2025-Jan 2026 (partial)', 'amount' => 213, 'spent_at' => '2026-03-13', 'description' => 'Back unit Aug 2–Jan 2, balance 911.00 unpaid'],
        ];

        foreach ($electricityRows as $row) {
            Expense::firstOrCreate(
                ['user_id' => $this->user->id, 'title' => $row['title'], 'spent_at' => $row['spent_at']],
                array_merge($row, ['user_id' => $this->user->id, 'category_id' => $bills?->id, 'is_recurring' => false])
            );
        }
    }

    // ──────────────────────────────────────────
    // PURCHASES
    // ──────────────────────────────────────────
    private function seedPurchases(): void
    {
        $catMap = [
            'Other Expense' => Category::where('name', 'Other Expense')->where('type', 'expense')->first()?->id,
            'Shopping'      => Category::where('name', 'Shopping')->where('type', 'expense')->first()?->id,
            'Grocery'       => Category::where('name', 'Food')->where('type', 'expense')->first()?->id,
            'Food'          => Category::where('name', 'Food')->where('type', 'expense')->first()?->id,
            'Clothing'      => Category::where('name', 'Shopping')->where('type', 'expense')->first()?->id,
        ];

        $rows = [
            ['item_name' => 'Motor Maintenance',                      'description' => 'Motor Maintenance',                                           'total_cost' => 4450.00,   'purchase_date' => '2025-02-15', 'expense_code' => 'Other Expense', 'payment' => 'Cash'],
            ['item_name' => 'Watson / Luxe Organix / Body Products',  'description' => 'Watson/Luxe Organix/Body Treats/SOME by me products',         'total_cost' => 1872.00,   'purchase_date' => '2025-02-15', 'expense_code' => 'Shopping',      'payment' => 'BPI'],
            ['item_name' => 'Good Luck Plastic - 2x Chair',           'description' => 'Good Luck Plastic - 2x Chair',                                'total_cost' => 554.00,    'purchase_date' => '2025-02-15', 'expense_code' => 'Other Expense', 'payment' => 'Cash'],
            ['item_name' => 'HandyMan Gaisano Davao',                 'description' => 'HandyMan Gaisano Davao',                                       'total_cost' => 2223.70,   'purchase_date' => '2025-02-15', 'expense_code' => 'Other Expense', 'payment' => 'BPI'],
            ['item_name' => 'Grocery - Gaisano Mall Davao',           'description' => 'Gaisano Mall Davao',                                           'total_cost' => 2202.13,   'purchase_date' => '2025-02-15', 'expense_code' => 'Grocery',       'payment' => 'Cash'],
            ['item_name' => 'Rice 5kg',                               'description' => 'Rice 5kg',                                                     'total_cost' => 260.00,    'purchase_date' => '2025-02-15', 'expense_code' => 'Food',          'payment' => 'Cash'],
            ['item_name' => 'Rice 25kg',                              'description' => 'Rice 25kg',                                                    'total_cost' => 995.00,    'purchase_date' => '2025-04-30', 'expense_code' => 'Food',          'payment' => 'Cash'],
            ['item_name' => 'Rice 25kg',                              'description' => 'Rice 25kg',                                                    'total_cost' => 935.00,    'purchase_date' => '2025-06-15', 'expense_code' => 'Food',          'payment' => 'Cash'],
            ['item_name' => 'Rice 2kg banay2x',                       'description' => 'Rice 2kg - P43 banay2x',                                       'total_cost' => 86.00,     'purchase_date' => '2025-08-26', 'expense_code' => 'Food',          'payment' => 'Cash'],
            ['item_name' => 'Rice 25kg',                              'description' => 'Rice 25kg',                                                    'total_cost' => 1015.00,   'purchase_date' => '2025-09-20', 'expense_code' => 'Food',          'payment' => 'Cash'],
            ['item_name' => 'Groceries',                              'description' => 'Groceries',                                                    'total_cost' => 1479.70,   'purchase_date' => '2026-02-14', 'expense_code' => 'Grocery',       'payment' => 'EastWest'],
            ['item_name' => 'Long sleeves (white) 2 pieces',          'description' => 'Long sleeves (white) 2 pieces',                                'total_cost' => 1060.00,   'purchase_date' => '2026-02-14', 'expense_code' => 'Clothing',      'payment' => 'EastWest'],
            ['item_name' => 'Motor Maintenance 2026',                 'description' => 'Motor Maintenance',                                           'total_cost' => 2613.00,   'purchase_date' => '2026-02-14', 'expense_code' => 'Other Expense', 'payment' => 'Cash'],
            ['item_name' => 'Gasul',                                  'description' => 'Gasul',                                                        'total_cost' => 1180.00,   'purchase_date' => '2026-02-16', 'expense_code' => 'Other Expense', 'payment' => 'Cash'],
            ['item_name' => 'Groceries Mar 2026',                     'description' => 'Groceries',                                                    'total_cost' => 3228.05,   'purchase_date' => '2026-03-13', 'expense_code' => 'Grocery',       'payment' => 'Cash'],
        ];

        foreach ($rows as $row) {
            Purchase::firstOrCreate(
                ['user_id' => $this->user->id, 'item_name' => $row['item_name'], 'purchase_date' => $row['purchase_date']],
                [
                    'user_id'       => $this->user->id,
                    'category_id'   => $catMap[$row['expense_code']] ?? null,
                    'item_name'     => $row['item_name'],
                    'description'   => $row['description'] . ' | Payment: ' . $row['payment'],
                    'total_cost'    => $row['total_cost'],
                    'is_installment' => false,
                    'purchase_date' => $row['purchase_date'],
                ]
            );
        }
    }

    // ──────────────────────────────────────────
    // INVESTMENTS  (Land + MP2 contributions as investments)
    // ──────────────────────────────────────────
    private function seedInvestments(): void
    {
        $realEstate = Category::where('name', 'Real Estate')->where('type', 'investment')->first();
        $mutualFund = Category::where('name', 'Mutual Fund')->where('type', 'investment')->first();

        $landRows = [
            ['name' => 'Land - Tagakpan', 'amount' => 194488.00, 'date' => '2025-02-15', 'desc' => 'Tagakpan lot purchase installment 1'],
            ['name' => 'Land - Bunawan 1', 'amount' => 70000.00,  'date' => '2025-02-24', 'desc' => 'Bunawan lot 1 purchase'],
            ['name' => 'Land - Tagakpan', 'amount' => 13892.00,  'date' => '2025-03-15', 'desc' => 'Tagakpan lot installment 2'],
            ['name' => 'Land - Bunawan 2', 'amount' => 22500.00,  'date' => '2025-03-22', 'desc' => 'Bunawan lot 2 installment 1'],
            ['name' => 'Land - Bunawan 1', 'amount' => 35000.00,  'date' => '2025-03-24', 'desc' => 'Bunawan lot 1 installment 2'],
            ['name' => 'Land - Bunawan 2', 'amount' => 22500.00,  'date' => '2025-04-21', 'desc' => 'Bunawan lot 2 installment 2'],
            ['name' => 'Land - Bunawan 2', 'amount' => 22500.00,  'date' => '2025-05-22', 'desc' => 'Bunawan lot 2 installment 3'],
            ['name' => 'Land - Tagakpan', 'amount' => 13892.00,  'date' => '2025-05-31', 'desc' => 'Tagakpan lot installment 3'],
            ['name' => 'Land - Bunawan 2', 'amount' => 22500.00,  'date' => '2025-06-20', 'desc' => 'Bunawan lot 2 installment 4'],
            ['name' => 'Land - Bunawan 2', 'amount' => 22500.00,  'date' => '2025-07-12', 'desc' => 'Bunawan lot 2 installment 5'],
            ['name' => 'Land - Bunawan 2', 'amount' => 22500.00,  'date' => '2025-08-14', 'desc' => 'Bunawan lot 2 installment 6'],
        ];

        foreach ($landRows as $row) {
            Investment::firstOrCreate(
                ['user_id' => $this->user->id, 'name' => $row['name'], 'purchase_date' => $row['date'], 'amount_invested' => $row['amount']],
                [
                    'user_id'         => $this->user->id,
                    'category_id'     => $realEstate?->id,
                    'name'            => $row['name'],
                    'type'            => 'real_estate',
                    'amount_invested'  => $row['amount'],
                    'current_value'   => $row['amount'],  // land appreciates; set to cost as baseline
                    'purchase_date'   => $row['date'],
                    'description'     => $row['desc'],
                ]
            );
        }

        // MP2 contributions as mutual fund investments
        $mp2Rows = [
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-02-16'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-02-16'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-03-10'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-03-10'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-04-21'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-04-21'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-05-01'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-05-01'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-06-27'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-06-27'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-07-29'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-07-29'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-08-28'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-08-28'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-09-30'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-09-30'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-10-30'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-10-30'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2025-11-30'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2025-12-30'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 2000, 'date' => '2026-01-31'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 2000, 'date' => '2026-01-31'],
            ['name' => 'MP2 - Mark Sarabia', 'amount' => 6000, 'date' => '2026-03-04'],
            ['name' => 'MP2 - Regine Donaire', 'amount' => 6000, 'date' => '2026-03-04'],
        ];

        foreach ($mp2Rows as $row) {
            Investment::firstOrCreate(
                ['user_id' => $this->user->id, 'name' => $row['name'], 'purchase_date' => $row['date'], 'amount_invested' => $row['amount']],
                [
                    'user_id'        => $this->user->id,
                    'category_id'    => $mutualFund?->id,
                    'name'           => $row['name'],
                    'type'           => 'mutual_fund',
                    'amount_invested' => $row['amount'],
                    'current_value'  => $row['amount'],
                    'purchase_date'  => $row['date'],
                    'description'    => 'PAG-IBIG MP2 Voluntary Savings contribution',
                ]
            );
        }
    }

    // ──────────────────────────────────────────
    // STOCKS
    // ──────────────────────────────────────────
    private function seedStocks(): void
    {
        // PhilStock equity purchases
        $stockRows = [
            ['symbol' => 'RCR',    'company' => 'RCR (REIT)',                                     'shares' => 100,    'buy_price' => 5.87,       'current_price' => 6.58,  'date' => '2024-12-11', 'notes' => 'PhilStock'],
            ['symbol' => 'RCR',    'company' => 'RCR (REIT)',                                     'shares' => 200,    'buy_price' => 5.85,       'current_price' => 6.58,  'date' => '2024-12-27', 'notes' => 'PhilStock'],
            ['symbol' => 'DDMPR',  'company' => 'DoubleDragon Meridian Park REIT',                'shares' => 1000,   'buy_price' => 1.03,       'current_price' => 1.00,  'date' => '2025-02-15', 'notes' => 'PhilStock; buy below 1.5'],
            ['symbol' => 'RCR',    'company' => 'RCR (REIT)',                                     'shares' => 300,    'buy_price' => 5.87,       'current_price' => 6.58,  'date' => '2025-02-15', 'notes' => 'PhilStock; buy below 6'],
            ['symbol' => 'RCR',    'company' => 'RCR (REIT)',                                     'shares' => 1000,   'buy_price' => 6.58,       'current_price' => 6.58,  'date' => '2025-06-02', 'notes' => 'PhilStock; buy below 6'],
            ['symbol' => 'RCR',    'company' => 'RCR (REIT)',                                     'shares' => 200,    'buy_price' => 6.57,       'current_price' => 6.58,  'date' => '2025-06-02', 'notes' => 'PhilStock; buy below 6'],
            ['symbol' => 'AREIT',  'company' => 'Ayala REIT Inc.',                                'shares' => 100,    'buy_price' => 39.70,      'current_price' => 39.70, 'date' => '2025-06-02', 'notes' => 'PhilStock; buy below 40'],
            ['symbol' => 'CREIT',  'company' => 'Citicore Energy REIT Corp.',                    'shares' => 1000,   'buy_price' => 3.34,       'current_price' => 3.34,  'date' => '2025-06-04', 'notes' => 'PhilStock; buy below 3'],
            ['symbol' => 'GLO',    'company' => 'Globe Telecom Inc.',                             'shares' => 5,      'buy_price' => 1518.00,    'current_price' => 1518.00,'date' => '2025-09-03', 'notes' => 'PhilStock; buy below 1600'],
            ['symbol' => 'TEL',    'company' => 'PLDT Inc.',                                      'shares' => 5,      'buy_price' => 1120.00,    'current_price' => 1120.00,'date' => '2025-09-03', 'notes' => 'PhilStock; buy below 1200'],
            ['symbol' => 'GSMI',   'company' => 'Ginebra San Miguel Inc.',                        'shares' => 20,     'buy_price' => 280.6255,   'current_price' => 280.63,'date' => '2025-10-02', 'notes' => 'PhilStock; buy below 290'],
            ['symbol' => 'VREIT',  'company' => 'VistaREIT Inc.',                                 'shares' => 1000,   'buy_price' => 1.50443,    'current_price' => 1.50,  'date' => '2025-10-02', 'notes' => 'PhilStock; buy below 1.3'],
        ];

        foreach ($stockRows as $row) {
            Stock::firstOrCreate(
                ['user_id' => $this->user->id, 'symbol' => $row['symbol'], 'purchase_date' => $row['date'], 'buy_price' => $row['buy_price']],
                [
                    'user_id'       => $this->user->id,
                    'symbol'        => $row['symbol'],
                    'company_name'  => $row['company'],
                    'shares'        => $row['shares'],
                    'buy_price'     => $row['buy_price'],
                    'current_price' => $row['current_price'],
                    'purchase_date' => $row['date'],
                    'notes'         => $row['notes'],
                ]
            );
        }

        // Fund investments (DragonFi, BPI Fund, GCash Fund) mapped as stocks with symbol = fund code
        $fundRows = [
            ['symbol' => 'MGTEF-DFI', 'company' => 'Manulife Global Technology Equity Feeder Fund (DragonFi)', 'shares' => 1, 'buy_price' => 2000.00, 'current_price' => 2000.00, 'date' => '2025-02-15', 'notes' => 'DragonFi fund'],
            ['symbol' => 'ALFM-GMI',  'company' => 'ALFM Global Multi-Asset Income Fund Inc. PHP (GCash)',     'shares' => 45.5096, 'buy_price' => 131.84, 'current_price' => 131.84,'date' => '2025-02-15', 'notes' => 'GCash Fund; 6000 total invested'],
            ['symbol' => 'ALFM-PBF',  'company' => 'ALFM Peso Bond Fund Inc. (BPI)',                           'shares' => 99,      'buy_price' => 401.44, 'current_price' => 401.44,'date' => '2025-02-16', 'notes' => 'BPI Fund; includes dividend reinvest 363.51'],
            ['symbol' => 'ALFM-GMI',  'company' => 'ALFM Global Multi-Asset Income Fund PHP Class (BPI)',      'shares' => 44.752,  'buy_price' => 67.04,  'current_price' => 67.04, 'date' => '2025-09-02', 'notes' => 'BPI Fund; buy below 65'],
            ['symbol' => 'MGTEF-DFI', 'company' => 'Manulife Global Technology Equity Feeder Fund (DragonFi)', 'shares' => 1, 'buy_price' => 5000.00, 'current_price' => 5000.00, 'date' => '2025-10-30', 'notes' => 'DragonFi fund additional'],
        ];

        foreach ($fundRows as $row) {
            Stock::firstOrCreate(
                ['user_id' => $this->user->id, 'symbol' => $row['symbol'], 'purchase_date' => $row['date'], 'buy_price' => $row['buy_price']],
                [
                    'user_id'       => $this->user->id,
                    'symbol'        => $row['symbol'],
                    'company_name'  => $row['company'],
                    'shares'        => $row['shares'],
                    'buy_price'     => $row['buy_price'],
                    'current_price' => $row['current_price'],
                    'purchase_date' => $row['date'],
                    'notes'         => $row['notes'],
                ]
            );
        }
    }

    // ──────────────────────────────────────────
    // DEBTS  (Personal + Business)
    // ──────────────────────────────────────────
    private function seedDebts(): void
    {
        // Personal debts
        $personalDebts = [
            [
                'lender_name'       => 'Mark Rezzel Sarabia',
                'amount'            => 10000.00,
                // Balance = principal + (principal * 0.10) = 11000, minus payments of 4000 = 7000 remaining
                'remaining_balance' => 7000.00,
                'interest_rate'     => 10.00,
                'due_date'          => '2025-06-30',
                'description'       => 'Personal loan at 10% interest. Start: 2024-12-13. Payments: 2000 on 2025-01-13, 2000 on 2025-01-15.',
                'status'            => 'active',
                'type'              => 'personal',
            ],
            [
                'lender_name'       => 'Personal Collection',
                'amount'            => 1670.00,
                'remaining_balance' => 0.00,
                'interest_rate'     => 0.00,
                'due_date'          => '2025-02-01',
                'description'       => 'Personal collection Jan 2025 — fully paid.',
                'status'            => 'paid',
                'type'              => 'personal',
            ],
            [
                'lender_name'       => 'Personal Collection',
                'amount'            => 2560.00,
                'remaining_balance' => 0.00,
                'interest_rate'     => 0.00,
                'due_date'          => '2025-03-20',
                'description'       => 'Personal collection Feb 2025 — fully paid.',
                'status'            => 'paid',
                'type'              => 'personal',
            ],
            [
                'lender_name'       => 'Personal Collection',
                'amount'            => 1891.00,
                'remaining_balance' => 0.00,
                'interest_rate'     => 0.00,
                'due_date'          => '2025-05-21',
                'description'       => 'Personal collection Apr 2025 — fully paid.',
                'status'            => 'paid',
                'type'              => 'personal',
            ],
            [
                'lender_name'       => 'Personal Collection',
                'amount'            => 2500.00,
                'remaining_balance' => 0.00,
                'interest_rate'     => 0.00,
                'due_date'          => '2025-08-30',
                'description'       => 'Personal collection Jul 2025 — fully paid.',
                'status'            => 'paid',
                'type'              => 'personal',
            ],
            [
                'lender_name'       => 'Personal Collection',
                'amount'            => 1841.75,
                'remaining_balance' => 1841.75,
                'interest_rate'     => 0.00,
                'due_date'          => '2025-10-31',
                'description'       => 'Personal collection Sep 2025 — outstanding.',
                'status'            => 'active',
                'type'              => 'personal',
            ],
        ];

        foreach ($personalDebts as $debt) {
            Debt::firstOrCreate(
                ['user_id' => $this->user->id, 'lender_name' => $debt['lender_name'], 'amount' => $debt['amount']],
                array_merge($debt, ['user_id' => $this->user->id])
            );
        }

        // Business debt (Mikay)
        // Daily interest = 5000 * 0.10 / 30 = 16.67
        // Start: 2026-01-31, 1 payment made, as of 2026-03-17 (45 days) balance = 5750
        $mikayDebt = Debt::firstOrCreate(
            ['user_id' => $this->user->id, 'lender_name' => 'Mikay', 'amount' => 5000.00],
            [
                'user_id'           => $this->user->id,
                'lender_name'       => 'Mikay',
                'amount'            => 5000.00,
                'remaining_balance' => 5750.00,
                'interest_rate'     => 10.00,
                'due_date'          => '2026-04-30',
                'description'       => 'Business loan. Start: 2026-01-31. Monthly interest 10% (500/month). Daily interest: ₱16.67. As of 2026-03-17 (45 days from last check): balance ₱5,750.',
                'status'            => 'active',
                'type'              => 'business',
                'business_name'     => 'Mikay Business',
            ]
        );

        // Seed payment record for Mikay
        Payment::firstOrCreate(
            ['user_id' => $this->user->id, 'debt_id' => $mikayDebt->id, 'payment_date' => '2026-03-17'],
            [
                'user_id'      => $this->user->id,
                'debt_id'      => $mikayDebt->id,
                'amount'       => 0.00,  // payment recorded but amount not settled yet (balance remains 5750)
                'payment_date' => '2026-03-17',
                'note'         => 'Balance check: 45 days accrued interest. Remaining balance ₱5,750. Daily interest rate: ₱16.67/day.',
            ]
        );
    }

    // ──────────────────────────────────────────
    // INSURANCE
    // ──────────────────────────────────────────
    private function seedInsurance(): void
    {
        $plan = InsurancePlan::firstOrCreate(
            ['user_id' => $this->user->id, 'plan_name' => 'BPI AIA VUL'],
            [
                'user_id'           => $this->user->id,
                'provider_name'     => 'BPI AIA',
                'plan_name'         => 'BPI AIA VUL',
                'coverage_type'     => 'Life',
                'coverage_amount'   => 500000.00,
                'premium_amount'    => 2022.94,
                'payment_frequency' => 'monthly',
                'next_payment_date' => '2026-04-11',
                'policy_number'     => null,
                'description'       => 'VUL life insurance + investment. Monthly: ₱2,022.94 (Insurance: ₱1,011.11 | Investment fund: ₱1,011.83). Fund: BPI-PHILAM PESO BOND FUND (balance as of Aug 2025: ₱23,275.27).',
            ]
        );

        // Generate 30 monthly payments from 2023-10 to 2026-02
        $paymentDate = new \DateTime('2023-10-14');
        for ($i = 0; $i < 30; $i++) {
            $amount = ($i === 0) ? 2022.95 : 2022.94;  // first payment was 2022.95
            InsurancePayment::firstOrCreate(
                ['user_id' => $this->user->id, 'insurance_plan_id' => $plan->id, 'payment_date' => $paymentDate->format('Y-m-d')],
                [
                    'user_id'            => $this->user->id,
                    'insurance_plan_id'  => $plan->id,
                    'amount'             => $amount,
                    'payment_date'       => $paymentDate->format('Y-m-d'),
                    'note'               => 'Monthly premium payment #' . ($i + 1) . '. Insurance portion: ₱1,011.11. Investment portion: ₱1,011.83.',
                ]
            );
            $paymentDate->modify('+1 month');
        }
    }

    // ──────────────────────────────────────────
    // MP2 PLANS
    // ──────────────────────────────────────────
    private function seedMP2Plans(): void
    {
        // Mark's MP2 plan — 2000/month at 7.1%, 5 years (2024-2028)
        // Total contributions: 414,000, Projected total: 481,371.08, Earnings: 67,371.08
        MP2Plan::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => "Mark Sarabia's MP2 Plan"],
            [
                'user_id'              => $this->user->id,
                'name'                 => "Mark Sarabia's MP2 Plan",
                'monthly_contribution' => 2000.00,
                'duration_years'       => 5,
                'start_date'           => '2024-01-01',
                'total_contributions'  => 414000.00,
                'projected_earnings'   => 67371.08,
                'notes'                => 'PAG-IBIG MP2 Voluntary Savings. Dividend rate: 7.1% p.a. (PAG-IBIG declared). Lump-sum ₱100k added Jan of 2026, 2027, 2028. Projected total at maturity (2028): ₱481,371.08.',
            ]
        );

        // Combined Mark + Regine plan
        MP2Plan::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => "Combined MP2 Plan (Mark + Regine)"],
            [
                'user_id'              => $this->user->id,
                'name'                 => "Combined MP2 Plan (Mark + Regine)",
                'monthly_contribution' => 4000.00,
                'duration_years'       => 5,
                'start_date'           => '2024-01-01',
                'total_contributions'  => 895371.08,
                'projected_earnings'   => 219341.40,
                'notes'                => 'Combined PAG-IBIG MP2 for Mark Sarabia and Regine Donaire. Rate: 7.1% p.a. Both contribute ₱2,000/month each. Lump-sum ₱100k/year each from 2026. Projected total (2028): ₱1,114,712.48.',
            ]
        );
    }

    // ──────────────────────────────────────────
    // FINANCIAL PLANS
    // ──────────────────────────────────────────
    private function seedFinancialPlans(): void
    {
        $plans = [
            ['name' => 'BRRR Strategy', 'description' => 'Buy, Renovate, Rent, Repeat — real estate investment strategy using rental income to fund next property purchase.'],
            ['name' => 'Tindahan (Bigasan)', 'description' => 'Small rice retail store business plan. Leverage existing rice purchase network.'],
            ['name' => 'Pastil in a Can', 'description' => 'Food product business — Pastil (Mindanaoan rice dish) packaged for retail/online selling.'],
            ['name' => 'Office Supplies Store', 'description' => 'Retail office supplies business targeting local schools, offices, and businesses.'],
            ['name' => 'Lechon Manok', 'description' => 'Roasted chicken (lechon manok) food business — high-demand, low startup cost.'],
            ['name' => 'Coffee Shop', 'description' => 'Small coffee shop or coffee cart business. Target foot traffic near commercial areas.'],
            ['name' => 'Internet Lounge', 'description' => 'Computer rental / internet cafe / gaming lounge business.'],
            ['name' => 'Water Refilling Station', 'description' => 'Water purification and refilling station — recurring income from neighborhood customers.'],
        ];

        foreach ($plans as $plan) {
            $fp = FinancialPlan::firstOrCreate(
                ['user_id' => $this->user->id, 'name' => $plan['name']],
                [
                    'user_id'     => $this->user->id,
                    'name'        => $plan['name'],
                    'description' => $plan['description'],
                    'start_date'  => '2026-01-01',
                    'status'      => 'active',
                ]
            );

            // Add a financial goal for each plan
            FinancialGoal::firstOrCreate(
                ['user_id' => $this->user->id, 'name' => 'Startup Capital - ' . $plan['name']],
                [
                    'user_id'             => $this->user->id,
                    'financial_plan_id'   => $fp->id,
                    'name'                => 'Startup Capital - ' . $plan['name'],
                    'target_amount'       => 100000.00,
                    'current_amount'      => 0.00,
                    'priority'            => 'medium',
                    'status'              => 'pending',
                ]
            );
        }
    }

    // ──────────────────────────────────────────
    // BUDGETS  (Installment tracking from Budgeting sheet)
    // ──────────────────────────────────────────
    private function seedBudgets(): void
    {
        $housing = Category::where('name', 'Housing')->where('type', 'expense')->first();
        $electronics = Category::where('name', 'Electronics')->where('type', 'purchase')->first();

        // Plan 1: 63,200 total / 6 installments of 10,533.33 — 4 paid
        Budget::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => 'Installment Plan - 63,200 (W)'],
            [
                'user_id'         => $this->user->id,
                'category_id'     => $housing?->id,
                'name'            => 'Installment Plan - 63,200 (W)',
                'amount'          => 63200.00,
                'period'          => 'monthly',
                'start_date'      => '2026-01-04',
                'end_date'        => '2026-06-30',
                'alert_threshold' => 80,
            ]
        );

        // Plan 2: 38,700 total / 6 installments of 6,450 — 2 paid (iPhone)
        Budget::firstOrCreate(
            ['user_id' => $this->user->id, 'name' => 'Installment Plan - iPhone 38,700'],
            [
                'user_id'         => $this->user->id,
                'category_id'     => $electronics?->id,
                'name'            => 'Installment Plan - iPhone 38,700',
                'amount'          => 38700.00,
                'period'          => 'monthly',
                'start_date'      => '2026-02-25',
                'end_date'        => '2026-07-31',
                'alert_threshold' => 80,
            ]
        );
    }
}
