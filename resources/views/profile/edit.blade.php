<x-customer-layout title="My Profile">
<div class="customer-profile-page">
    <section class="form-page-heading"><span class="eyebrow">ACCOUNT SETTINGS</span><h2>Personal details</h2><p>Keep your account information up to date.</p></section>
    <form class="admin-form panel customer-profile-form" method="POST" action="{{ route('profile.update') }}">@csrf @method('PUT')
        <div class="field-grid"><div class="field"><label>Full name</label><div class="field__control"><input name="name" value="{{ old('name',$user->name) }}" required></div></div><div class="field"><label>Email address</label><div class="field__control"><input name="email" type="email" value="{{ old('email',$user->email) }}" required></div></div><div class="field"><label>Phone number</label><div class="field__control"><input name="phone" type="tel" value="{{ old('phone',$user->phone) }}" placeholder="Your phone number" required></div></div></div>
        <hr><h3>Change password <small>Leave blank to keep current password</small></h3><div class="field-grid"><div class="field"><label>Current password</label><div class="field__control"><input type="password" name="current_password"></div></div><div class="field"><label>New password</label><div class="field__control"><input type="password" name="password"></div></div></div><div class="field"><label>Confirm password</label><div class="field__control"><input type="password" name="password_confirmation"></div></div><button class="button button--dark">Save changes <span>→</span></button>
    </form>
</div>
</x-customer-layout>
