<ul class="nav nav-pills mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.branch.dashboard') ? 'active' : '' }}" href="{{ route('accounting.branch.dashboard') }}">Dashboard</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.branch.revenue') ? 'active' : '' }}" href="{{ route('accounting.branch.revenue') }}">Revenue</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.branch.expenses') ? 'active' : '' }}" href="{{ route('accounting.branch.expenses') }}">Expenses</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.branch.profitability') ? 'active' : '' }}" href="{{ route('accounting.branch.profitability') }}">Profitability</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.branch.cash-position') ? 'active' : '' }}" href="{{ route('accounting.branch.cash-position') }}">Cash Position</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('accounting.branch.outstanding') ? 'active' : '' }}" href="{{ route('accounting.branch.outstanding') }}">Outstanding</a>
    </li>
</ul>
