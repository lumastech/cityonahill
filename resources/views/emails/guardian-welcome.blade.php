<x-mail::message>
# Welcome to the Parent Portal

Dear {{ $user->name }},

A parent portal account has been created for you. You can now log in to view your child's academic progress, attendance, and fee information.

**Your login details:**

- **Email:** {{ $user->email }}
- **Temporary Password:** `{{ $temporaryPassword }}`

<x-mail::button :url="$loginUrl">
Access Parent Portal
</x-mail::button>

Please change your password after your first login.

Thanks,
{{ config('app.name') }}
</x-mail::message>
