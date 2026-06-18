<tr class="{{ $account->is_group ? 'font-weight-bold' : '' }} {{ !$account->is_active ? 'text-muted' : '' }}">
    <td>{{ $account->code }}</td>
    <td style="padding-left:{{ ($level * 20) + 10 }}px">
        @if($account->is_group)<i class="fa fa-folder-open" style="color:#f39c12"></i>@else<i class="fa fa-file-text-o" style="color:#3498db"></i>@endif
        {{ $account->name }}
    </td>
    <td><span class="badge badge-{{ match($account->type) { 'asset'=>'primary','liability'=>'warning','income'=>'success','expense'=>'danger','equity'=>'info',default=>'secondary' } }}">{{ ucfirst($account->type) }}</span></td>
    <td>{{ $account->is_group ? 'Group' : 'Ledger' }}</td>
    <td class="text-right">{{ $account->opening_balance > 0 ? '₹'.number_format($account->opening_balance, 2) : '-' }}</td>
    <td>@if($account->is_active)<span class="badge badge-success">Active</span>@else<span class="badge badge-secondary">Inactive</span>@endif</td>
    <td>
        <a href="{{ route('accounting.accounts.edit', $account->id) }}" class="btn btn-info btn-sm"><i class="fa fa-edit"></i></a>
        @if($account->isDeleteable())
        <form action="{{ route('accounting.accounts.destroy', $account->id) }}" method="POST" style="display:inline">@csrf @method('DELETE')
            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete {{ $account->name }}?')"><i class="fa fa-trash"></i></button>
        </form>
        @endif
    </td>
</tr>
@if($account->children->count())
    @foreach($account->children as $child)
        @include('accounting.accounts.partials.account_row', ['account' => $child, 'level' => $level + 1])
    @endforeach
@endif
