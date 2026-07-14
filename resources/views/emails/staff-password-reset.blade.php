<x-mail::message>
# Your password has been reset

Dear {{ $user->name }},

An administrator has reset the password for your {{ config('app.name') }} account.

**Your login details:**

- **Email:** {{ $user->email }}
- **Temporary Password:** `{{ $temporaryPassword }}`

<x-mail::button :url="$loginUrl">
Log In
</x-mail::button>

Please change this password immediately after logging in, from **Account settings**.

If you did not expect this change, contact your school administrator.

Thanks,
{{ config('app.name') }}
</x-mail::message>
