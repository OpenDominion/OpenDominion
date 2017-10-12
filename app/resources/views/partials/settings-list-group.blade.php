<div class="list-group">
    {{-- todo: refactor. make array and loop through --}}
    <a href="{{ route('settings.account') }}" class="list-group-item {{ Route::is('settings.account') ? 'active' : null }}">Account</a>
    <a href="{{ route('settings.notifications') }}" class="list-group-item {{ Route::is('settings.notifications') ? 'active' : null }}">Notifications</a>
    <a href="{{ route('settings.security') }}" class="list-group-item {{ Route::is('settings.security') ? 'active' : null }}">Security</a>
</div>
