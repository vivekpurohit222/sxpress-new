<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:SuperAdmin']);
    }

    /**
     * List all accounts in tree structure.
     */
    public function index(Request $request)
    {
        $type = $request->type;

        $query = Account::with('children')->roots()->orderBy('code');

        if ($type) {
            $query->where('type', $type);
        }

        $accounts = $query->get();

        // Flat list for search/filter
        $allAccounts = Account::orderBy('code')->get();

        $types = ['asset', 'liability', 'income', 'expense', 'equity'];

        return view('accounting.accounts.index', compact('accounts', 'allAccounts', 'types', 'type'));
    }

    /**
     * Show create form.
     */
    public function create()
    {
        $parentAccounts = Account::active()->groups()->orderBy('code')->get();
        $types = ['asset', 'liability', 'income', 'expense', 'equity'];

        // Generate next code
        $lastCode = Account::orderByDesc('code')->value('code');
        $nextCode = $lastCode ? str_pad((int)$lastCode + 1, 4, '0', STR_PAD_LEFT) : '1001';

        return view('accounting.accounts.create', compact('parentAccounts', 'types', 'nextCode'));
    }

    /**
     * Store new account.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'            => 'required|string|max:10|unique:accounts,code',
            'name'            => 'required|string|max:150',
            'type'            => 'required|in:asset,liability,income,expense,equity',
            'parent_id'       => 'nullable|exists:accounts,id',
            'is_group'        => 'nullable|boolean',
            'opening_balance' => 'nullable|numeric',
        ]);

        $validated['is_group'] = (bool) ($validated['is_group'] ?? false);
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['is_system'] = false;
        $validated['is_active'] = true;

        // If parent selected, inherit type from parent
        if ($validated['parent_id']) {
            $parent = Account::find($validated['parent_id']);
            if ($parent) {
                $validated['type'] = $parent->type;
            }
        }

        Account::create($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Account '{$validated['name']}' created.");
    }

    /**
     * Edit form.
     */
    public function edit($id)
    {
        $account = Account::findOrFail($id);
        $parentAccounts = Account::active()->groups()
            ->where('id', '!=', $id)
            ->orderBy('code')
            ->get();
        $types = ['asset', 'liability', 'income', 'expense', 'equity'];

        return view('accounting.accounts.edit', compact('account', 'parentAccounts', 'types'));
    }

    /**
     * Update account.
     */
    public function update(Request $request, $id)
    {
        $account = Account::findOrFail($id);

        $validated = $request->validate([
            'code'            => 'required|string|max:10|unique:accounts,code,' . $id,
            'name'            => 'required|string|max:150',
            'type'            => 'required|in:asset,liability,income,expense,equity',
            'parent_id'       => 'nullable|exists:accounts,id',
            'is_group'        => 'nullable|boolean',
            'opening_balance' => 'nullable|numeric',
            'is_active'       => 'nullable|boolean',
        ]);

        $validated['is_group'] = (bool) ($validated['is_group'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;

        // Prevent making it non-group if it has children
        if (!$validated['is_group'] && $account->children()->exists()) {
            return back()->withErrors(['is_group' => 'Cannot unmark as group — has child accounts.']);
        }

        $account->update($validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Account '{$account->name}' updated.");
    }

    /**
     * Delete account (only if not system and no children).
     */
    public function destroy($id)
    {
        $account = Account::findOrFail($id);

        if (!$account->isDeleteable()) {
            return back()->withErrors(['Cannot delete this account — it is a system account or has child accounts.']);
        }

        $account->delete();

        return redirect()->route('accounting.accounts.index')
            ->with('success', "Account deleted.");
    }
}
