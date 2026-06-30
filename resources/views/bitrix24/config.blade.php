<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configure Salestrail Integration</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0B0F19;
            --bg-surface: #151B2C;
            --bg-surface-hover: #1E263F;
            --primary: #6366F1;
            --primary-hover: #4F46E5;
            --success: #10B981;
            --text-main: #F3F4F6;
            --text-muted: #9CA3AF;
            --border: #24304F;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-base);
            color: var(--text-main);
            min-height: 100vh;
            padding: 1.5rem;
            line-height: 1.5;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
        }

        .card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            background-color: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            color: var(--text-main);
            font-family: inherit;
            transition: var(--transition);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(99,102,241,0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-submit {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            width: 100%;
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .alert-success {
            background-color: rgba(16,185,129,0.15);
            border: 1px solid var(--success);
            color: var(--success);
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Configure Salestrail Integration</h1>
        
        @if(session('success'))
            <div class="alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background-color: rgba(239, 68, 68, 0.15); border: 1px solid var(--danger); color: var(--danger); padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; font-weight: 500;">
                <ul style="list-style: none; padding: 0;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card" style="margin-bottom: 1.5rem;">
            <h3 style="margin-bottom: 1rem; color: var(--primary); font-size: 1rem;">Salestrail Integration Hook</h3>
            <div style="display: flex; align-items: center; gap: 0.5rem; background-color: var(--bg-base); border: 1px solid var(--border); padding: 0.75rem 1rem; border-radius: 0.5rem;">
                <span style="font-family: monospace; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-grow: 1; color: var(--text-muted);" id="webhook-url">{{ url('/api/webhooks/' . $bitrixAccount->tenant->uuid . '/salestrail') }}</span>
                <button type="button" style="background: none; border: none; color: var(--primary); cursor: pointer; font-size: 0.875rem; font-weight: 600; padding: 0.25rem; transition: var(--transition);" onclick="copyToClipboard()">Copy</button>
            </div>
            <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.5rem;">Use this URL in your Salestrail dashboard to send call data to this integration.</p>
        </div>

        <div class="card">
            <form method="POST" action="{{ route('bitrix24.config.save') }}">
                @csrf
                
                <!-- Bitrix24 parameters -->
                <input type="hidden" name="DOMAIN" value="{{ request('DOMAIN') }}">
                <input type="hidden" name="member_id" value="{{ request('member_id') }}">
                
                <h3 style="margin-bottom: 1rem; color: var(--primary); font-size: 1rem;">Salestrail Settings</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">User</label>
                        <input type="text" name="salestrail_user" id="salestrail-user" class="form-input" placeholder="Enter Salestrail User" value="{{ old('salestrail_user', $salestrailAccount->user ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="salestrail_password" id="salestrail-password" class="form-input" placeholder="Enter Salestrail Password">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">API Key</label>
                    <input type="text" name="salestrail_api_key" id="salestrail-api-key" class="form-input" placeholder="Enter Salestrail API Key" value="{{ old('salestrail_api_key', $salestrailAccount->api_key ?? '') }}">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">API URL</label>
                        <input type="text" name="salestrail_api_url" id="salestrail-api-url" class="form-input" placeholder="Enter Salestrail API URL" value="{{ old('salestrail_api_url', $salestrailAccount->api_url ?? '') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Webhook Secret</label>
                        <input type="text" name="salestrail_webhook_secret" id="salestrail-webhook-secret" class="form-input" placeholder="Enter Salestrail Webhook Secret" value="{{ old('salestrail_webhook_secret', $salestrailAccount->webhook_secret ?? '') }}">
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <button type="submit" class="btn-submit">Save Settings</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function copyToClipboard() {
            const text = document.getElementById('webhook-url').innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.querySelector('button[onclick="copyToClipboard()"]');
                const oldText = btn.innerText;
                btn.innerText = 'Copied!';
                setTimeout(() => {
                    btn.innerText = oldText;
                }, 1500);
            });
        }
    </script>
</body>
</html>