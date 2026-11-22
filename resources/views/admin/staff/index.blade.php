@extends('admin.layout')

@section('title', 'Staff')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Staff accounts</h1>
            <p>Create individual sign-ins for employees. Staff can access products, inventory, orders, and sales.</p>
        </div>
    </div>

    <section class="panel" style="padding: 18px; margin-bottom: 18px;">
        <h2 class="panel-title">Add a staff member</h2>
        <form method="POST" action="{{ route('admin.staff.store') }}" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-top:14px;">
            @csrf
            <div>
                <label for="name">Name</label>
                <input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="255" style="display:block; width:100%; margin-top:6px; padding:9px; border:1px solid #302a3b; border-radius:6px; background:#14111b; color:#fff;">
                @error('name') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required style="display:block; width:100%; margin-top:6px; padding:9px; border:1px solid #302a3b; border-radius:6px; background:#14111b; color:#fff;">
                @error('email') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password">Temporary password</label>
                <div class="staff-password-field">
                    <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Show password" aria-pressed="false">
                        <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
                @error('password') <p class="field-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password_confirmation">Confirm password</label>
                <div class="staff-password-field">
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="password_confirmation" aria-label="Show password" aria-pressed="false">
                        <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
            </div>
            <div style="grid-column:1 / -1;">
                <button class="primary-button" type="submit">Create staff account</button>
            </div>
        </form>
    </section>

    <section class="panel" style="padding: 18px; margin-bottom: 18px;">
        <h2 class="panel-title">Add an admin account</h2>
        <p style="margin-top:6px;">Admin accounts can manage all store areas and staff accounts.</p>
        <form method="POST" action="{{ route('admin.staff.admin.store') }}" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap:12px; margin-top:14px;">
            @csrf
            <div>
                <label for="admin_name">Name</label>
                <input id="admin_name" name="name" value="{{ old('name') }}" autocomplete="name" required maxlength="255" style="display:block; width:100%; margin-top:6px; padding:9px; border:1px solid #302a3b; border-radius:6px; background:#14111b; color:#fff;">
            </div>
            <div>
                <label for="admin_email">Email</label>
                <input id="admin_email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required style="display:block; width:100%; margin-top:6px; padding:9px; border:1px solid #302a3b; border-radius:6px; background:#14111b; color:#fff;">
            </div>
            <div>
                <label for="admin_password">Temporary password</label>
                <div class="staff-password-field">
                    <input id="admin_password" type="password" name="password" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="admin_password" aria-label="Show password" aria-pressed="false">
                        <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
            </div>
            <div>
                <label for="admin_password_confirmation">Confirm password</label>
                <div class="staff-password-field">
                    <input id="admin_password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                    <button class="password-toggle" type="button" data-password-toggle aria-controls="admin_password_confirmation" aria-label="Show password" aria-pressed="false">
                        <svg class="password-eye-open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="password-eye-closed" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a15.8 15.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7c1.4 0 2.7-.3 3.8-.8"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
            </div>
            <div style="grid-column:1 / -1;">
                <button class="primary-button" type="submit">Create admin account</button>
            </div>
        </form>
    </section>

    <section class="panel" style="margin-bottom:18px;">
        <div class="table-wrap">
            @if ($adminMembers->isEmpty())
                <div class="empty-state">No admin accounts yet.</div>
            @else
                <table class="data-table">
                    <thead>
                        <tr><th>NAME</th><th>EMAIL</th><th>ACCESS</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($adminMembers as $admin)
                            <tr>
                                <td>{{ $admin->name }}</td>
                                <td>{{ $admin->email }}</td>
                                <td>Administrator / owner</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <section class="panel">
        <div class="table-wrap">
            @if ($staffMembers->isEmpty())
                <div class="empty-state">No staff accounts yet.</div>
            @else
                <table class="data-table">
                    <thead>
                        <tr><th>NAME</th><th>EMAIL</th><th>ACCESS</th><th>ACTION</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($staffMembers as $staff)
                            <tr>
                                <td>{{ $staff->name }}</td>
                                <td>{{ $staff->email }}</td>
                                <td>Products, inventory, orders, sales</td>
                                <td>
                                    <form id="remove-staff-{{ $staff->id }}" method="POST" action="{{ route('admin.staff.destroy', $staff) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="table-action" type="button" data-staff-remove-form="remove-staff-{{ $staff->id }}" data-staff-name="{{ $staff->name }}">Remove access</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>

    <dialog class="staff-remove-dialog" id="staffRemoveDialog" aria-labelledby="staffRemoveTitle" aria-describedby="staffRemoveMessage">
        <div class="staff-remove-dialog-icon" aria-hidden="true">!</div>
        <h2 id="staffRemoveTitle">Remove staff access?</h2>
        <p id="staffRemoveMessage">This staff member will no longer be able to sign in.</p>
        <div class="staff-remove-dialog-actions">
            <form method="dialog">
                <button class="secondary-button" type="submit">Keep access</button>
            </form>
            <button class="staff-remove-confirm" id="confirmStaffRemove" type="button">Remove access</button>
        </div>
    </dialog>
@endsection

@push('styles')
    <style>
        .staff-password-field { position: relative; margin-top: 6px; }
        .staff-password-field input { display: block; width: 100%; margin: 0; padding: 9px 38px 9px 9px; border: 1px solid #302a3b; border-radius: 6px; background: #14111b; color: #fff; }
        .password-toggle { position: absolute; top: 50%; right: 7px; display: grid; width: 27px; height: 27px; place-items: center; padding: 4px; border-radius: 4px; color: #aaa4b2; transform: translateY(-50%); }
        .password-toggle:hover, .password-toggle:focus-visible { background: #211b2c; color: #fff; }
        .password-toggle:focus-visible, .staff-remove-dialog-actions button:focus-visible { outline: 2px solid #a78bfa; outline-offset: 2px; }
        .password-toggle svg { width: 17px; height: 17px; fill: none; stroke: currentColor; stroke-linecap: round; stroke-linejoin: round; stroke-width: 1.7; }
        .password-eye-closed { display: none; }
        .password-toggle[aria-pressed="true"] .password-eye-open { display: none; }
        .password-toggle[aria-pressed="true"] .password-eye-closed { display: block; }
        .staff-remove-dialog { width: min(390px, calc(100vw - 32px)); padding: 24px; border: 1px solid #393044; border-radius: 12px; background: #17141f; color: #f4f1ff; box-shadow: 0 22px 70px rgba(0,0,0,.55); }
        .staff-remove-dialog::backdrop { background: rgba(5,4,8,.72); backdrop-filter: blur(3px); }
        .staff-remove-dialog-icon { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(255,120,136,.3); border-radius: 50%; background: rgba(151,34,52,.15); color: #ff7888; font-size: 20px; font-weight: 700; }
        .staff-remove-dialog h2 { margin: 15px 0 6px; font: 600 17px 'Outfit', sans-serif; }
        .staff-remove-dialog p { margin: 0; color: #aaa4b2; font-size: 11px; line-height: 1.5; }
        .staff-remove-dialog-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 22px; }
        .staff-remove-dialog-actions form { margin: 0; }
        .staff-remove-dialog-actions button { min-height: 32px; padding: 0 12px; border: 1px solid #393044; border-radius: 6px; font: inherit; font-size: 10px; cursor: pointer; }
        .staff-remove-confirm { border-color: #9a3445 !important; background: #8d293a; color: #fff; font-weight: 600; }
        .staff-remove-confirm:hover { background: #a83246; }
    </style>
@endpush

@push('scripts')
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(button => {
            const input = document.getElementById(button.getAttribute('aria-controls'));
            button.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-pressed', String(show));
                button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            });
        });

        const staffRemoveDialog = document.getElementById('staffRemoveDialog');
        const staffRemoveMessage = document.getElementById('staffRemoveMessage');
        const confirmStaffRemove = document.getElementById('confirmStaffRemove');
        let pendingStaffRemoveForm = null;

        document.querySelectorAll('[data-staff-remove-form]').forEach(button => {
            button.addEventListener('click', () => {
                pendingStaffRemoveForm = document.getElementById(button.dataset.staffRemoveForm);
                staffRemoveMessage.textContent = `Remove access for "${button.dataset.staffName}"? They will no longer be able to sign in.`;
                if (typeof staffRemoveDialog.showModal !== 'function') {
                    if (window.confirm(staffRemoveMessage.textContent)) pendingStaffRemoveForm.requestSubmit();
                    return;
                }
                staffRemoveDialog.showModal();
                confirmStaffRemove.focus();
            });
        });

        confirmStaffRemove.addEventListener('click', () => pendingStaffRemoveForm?.requestSubmit());
        staffRemoveDialog.addEventListener('close', () => {
            pendingStaffRemoveForm = null;
        });
        staffRemoveDialog.addEventListener('click', event => {
            if (event.target === staffRemoveDialog) staffRemoveDialog.close();
        });
    </script>
@endpush
