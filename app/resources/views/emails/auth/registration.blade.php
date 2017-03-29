@component('mail::message')
Hello {{ $user->display_name }} and welcome to OpenDominion!

Before you can start playing you need to activate your account:

@component('mail::button', ['url' => route('auth.activate', $user->activation_code)])
Activate
@endcomponent

If that doesn't work, try pasting this in the browser: {{ route('auth.activate', $user->activation_code) }}

For reporting issues, use the [issue tracker](https://github.com/WaveHack/OpenDominion/issues) on GitHub.

Have fun playing!

OpenDominion
@endcomponent
