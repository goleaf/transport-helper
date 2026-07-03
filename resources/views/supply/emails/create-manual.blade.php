@extends('layouts.app')

@section('title')
Manual Inbound Email
@endsection

@section('content')
<header>
    <p><a href="{{ route('supply.emails.index') }}">Back to emails</a></p>
    <h1>Manual Inbound Email</h1>
</header>

@if ($errors->any())
    <section>
        <h2>Errors</h2>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </section>
@endif

<form method="post" action="{{ route('supply.emails.manual.store') }}" enctype="multipart/form-data">
    @csrf

    <div>
        <label>
            Company
            <select class="select select-bordered select-primary" name="company_id" required>
                @foreach ($companies as $company)
                    <option value="{{ $company->id }}" @selected((int) old('company_id') === $company->id)>{{ $company->name }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div>
        <label>
            Email account
            <select class="select select-bordered select-primary" name="email_account_id">
                <option value="">None</option>
                @foreach ($emailAccounts as $emailAccount)
                    <option value="{{ $emailAccount->id }}" @selected((int) old('email_account_id') === $emailAccount->id)>
                        {{ $emailAccount->name }} {{ $emailAccount->email_address }}
                    </option>
                @endforeach
            </select>
        </label>
    </div>

    <div>
        <label>From email <input class="input input-bordered input-primary" name="from_email" value="{{ old('from_email') }}" required></label>
    </div>

    <div>
        <label>To email <input class="input input-bordered input-primary" name="to[]" value="{{ old('to.0') }}"></label>
    </div>

    <div>
        <label>CC email <input class="input input-bordered input-primary" name="cc[]" value="{{ old('cc.0') }}"></label>
    </div>

    <div>
        <label>Subject <input class="input input-bordered input-primary" name="subject" value="{{ old('subject') }}"></label>
    </div>

    <div>
        <label>Body text <textarea class="textarea textarea-bordered textarea-primary" name="body_text" rows="8">{{ old('body_text') }}</textarea></label>
    </div>

    <div>
        <label>Received at <input class="input input-bordered input-primary" name="received_at" value="{{ old('received_at') }}"></label>
    </div>

    <div>
        <label>Message ID <input class="input input-bordered input-primary" name="message_id" value="{{ old('message_id') }}"></label>
    </div>

    <div>
        <label>Thread ID <input class="input input-bordered input-primary" name="thread_id" value="{{ old('thread_id') }}"></label>
    </div>

    <div>
        <label>Attachments <input class="file-input file-input-bordered file-input-primary" type="file" name="attachments[]" multiple></label>
    </div>

    <div>
        <label>
            <input class="checkbox checkbox-primary" type="checkbox" name="analyze" value="1" @checked(old('analyze'))>
            Analyze immediately
        </label>
    </div>

    <div>
        <label>
            Analyzer
            <select class="select select-bordered select-primary" name="analyzer">
                <option value="rule_based">Rule based</option>
                <option value="fake">Fake</option>
                <option value="external">External placeholder</option>
            </select>
        </label>
    </div>

    <x-supply.button type="submit">Store inbound email</x-supply.button>
</form>
@endsection
