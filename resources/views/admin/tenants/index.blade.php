<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Tenant Integration Console</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-base: #0B0F19;
            --bg-surface: #151B2C;
            --bg-surface-hover: #1E263F;
            --primary: #6366F1;
            --primary-hover: #4F46E5;
            --primary-glow: rgba(99, 102, 241, 0.15);
            --success: #10B981;
            --success-glow: rgba(16, 185, 129, 0.15);
            --danger: #EF4444;
            --danger-glow: rgba(239, 68, 68, 0.15);
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
            padding: 2.5rem;
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Header Style */
        header {
            margin-bottom: 3rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            font-size: 2.25rem;
            font-weight: 700;
            background: linear-gradient(135deg, #F3F4F6 0%, #9CA3AF 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.025em;
        }

        header p {
            color: var(--text-muted);
            margin-top: 0.5rem;
            font-size: 1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            padding: 1.5rem;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
            box-shadow: 0 12px 24px -10px var(--primary-glow);
        }

        .stat-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-top: 0.5rem;
            color: var(--text-main);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 4rem;
            height: 4rem;
            background: radial-gradient(circle, var(--primary-glow) 0%, transparent 70%);
            pointer-events: none;
        }

        /* Success Message */
        .alert-success {
            background-color: var(--success-glow);
            border: 1px solid var(--success);
            color: var(--success);
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 2rem;
            font-weight: 500;
            animation: fadeIn 0.4s ease-out;
        }

        /* Tenants List */
        .card {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th {
            background-color: rgba(21, 27, 44, 0.6);
            padding: 1.25rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            font-size: 0.95rem;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background-color: var(--bg-surface-hover);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-active {
            background-color: var(--success-glow);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .badge-inactive {
            background-color: var(--danger-glow);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        /* Webhook url box */
        .webhook-box {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--bg-base);
            border: 1px solid var(--border);
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            max-width: 320px;
        }

        .webhook-url {
            font-family: monospace;
            font-size: 0.8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            flex-grow: 1;
            color: var(--text-muted);
        }

        .btn-copy {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            padding: 0.25rem;
            transition: var(--transition);
        }

        .btn-copy:hover {
            color: var(--primary-hover);
        }

        /* Buttons styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-toggle {
            background-color: var(--primary);
            color: var(--text-main);
        }

        .btn-toggle:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-logs {
            background-color: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border);
            margin-left: 0.5rem;
        }

        .btn-logs:hover {
            background-color: var(--bg-surface-hover);
            color: var(--text-main);
        }

        /* Modal styling */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(11, 15, 25, 0.8);
            backdrop-filter: blur(8px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: var(--bg-surface);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            width: 100%;
            max-width: 800px;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: modalSlideUp 0.3s ease-out;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }

        .form-group {
            margin-bottom: 1.5rem;
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
            box-shadow: 0 0 0 2px var(--primary-glow);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
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
        }

        .btn-submit:hover {
            background-color: var(--primary-hover);
        }

        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-muted);
            font-size: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            color: var(--text-main);
        }

        .modal-body {
            padding: 1.5rem;
            overflow-y: auto;
            flex-grow: 1;
        }

        .logs-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .log-item {
            background-color: var(--bg-base);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 1rem;
        }

        .log-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .log-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 0.5rem;
        }

        .log-payload {
            background-color: #05070c;
            border: 1px solid var(--border);
            border-radius: 0.375rem;
            padding: 0.5rem;
            font-family: monospace;
            font-size: 0.75rem;
            overflow-x: auto;
            max-height: 120px;
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(30px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1>Integration Console</h1>
            <p>Manage and activate dynamic Salestrail & Bitrix24 Multi-Tenant operations.</p>
        </div>
        <div>
            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-logs" style="border-color: var(--danger); color: var(--danger);">
                    Log Out
                </button>
            </form>
        </div>
    </header>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Tenants</div>
            <div class="stat-value">{{ $stats['total'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active</div>
            <div class="stat-value" style="color: var(--success)">{{ $stats['active'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Inactive</div>
            <div class="stat-value" style="color: var(--danger)">{{ $stats['inactive'] }}</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Sync Action Logs</div>
            <div class="stat-value">{{ $stats['logs_count'] }}</div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success">
            {{ session('success') }}
        </div>
    @endif

    <!-- Tenants table -->
    <div class="card">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Bitrix Domain</th>
                        <th>Salestrail Integration Hook</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tenants as $tenant)
                        @php
                            $webhookUrl = url('/api/webhooks/' . $tenant->uuid . '/salestrail');
                        @endphp
                        <tr>
                            <td>
                                <strong style="color: var(--text-main)">{{ $tenant->company_name }}</strong>
                                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 0.25rem;">UUID: {{ $tenant->uuid }}</div>
                            </td>
                            <td>{{ $tenant->bitrixAccount->bitrix_domain ?? 'N/A' }}</td>
                            <td>
                                <div class="webhook-box">
                                    <span class="webhook-url" id="hook-{{ $tenant->id }}">{{ $webhookUrl }}</span>
                                    <button class="btn-copy" onclick="copyWebhook('hook-{{ $tenant->id }}')">Copy</button>
                                </div>
                            </td>
                            <td>
                                <span class="badge {{ $tenant->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $tenant->status }}
                                </span>
                            </td>
                            <td>
                                <form action="{{ route('admin.tenants.toggle-status', $tenant->id) }}" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-toggle">
                                        {{ $tenant->status === 'active' ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                                <button class="btn btn-logs" onclick="showConfig({{ $tenant->id }}, '{{ addslashes($tenant->company_name) }}', '{{ $tenant->bitrixAccount->client_id ?? '' }}', '{{ $tenant->bitrixAccount->client_secret ?? '' }}', '{{ $tenant->bitrixAccount->bitrix_domain ?? '' }}', '{{ $tenant->salestrailAccount->user ?? '' }}', '{{ $tenant->salestrailAccount->password ?? '' }}', '{{ $tenant->salestrailAccount->api_key ?? '' }}', '{{ $tenant->salestrailAccount->api_url ?? '' }}', '{{ $tenant->salestrailAccount->webhook_secret ?? '' }}')">
                    Config
                </button>
                                <button class="btn btn-logs" onclick="showLogs({{ $tenant->id }}, '{{ addslashes($tenant->company_name) }}')">
                                    View Logs
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                                No tenants found. Register a company in the database to get started.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Configuration Modal -->
<div class="modal" id="config-modal">
    <div class="modal-content" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title" id="config-modal-title">Configure Integration</h2>
            <button class="modal-close" onclick="closeConfig()">&times;</button>
        </div>
        <div class="modal-body">
            <form id="config-form" method="POST">
                @csrf
                <h3 style="margin-bottom: 1rem; color: var(--primary); font-size: 1.1rem;">Bitrix24 Credentials</h3>
                <div class="form-group">
                    <label class="form-label">Bitrix24 Domain</label>
                    <input type="text" name="bitrix_domain" id="config-bitrix-domain" class="form-input" placeholder="e.g., your-company.bitrix24.com">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Client ID</label>
                        <input type="text" name="bitrix_client_id" id="config-bitrix-client-id" class="form-input" placeholder="Enter Bitrix Client ID">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Secret</label>
                        <input type="password" name="bitrix_client_secret" id="config-bitrix-client-secret" class="form-input" placeholder="Enter Bitrix Client Secret">
                    </div>
                </div>

                <h3 style="margin: 1rem 0; color: var(--primary); font-size: 1.1rem;">Salestrail Settings</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">User</label>
                        <input type="text" name="salestrail_user" id="config-salestrail-user" class="form-input" placeholder="Enter Salestrail User">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password" name="salestrail_password" id="config-salestrail-password" class="form-input" placeholder="Enter Salestrail Password">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">API Key</label>
                    <input type="text" name="salestrail_api_key" id="config-salestrail-api-key" class="form-input" placeholder="Enter Salestrail API Key">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">API URL</label>
                        <input type="text" name="salestrail_api_url" id="config-salestrail-api-url" class="form-input" placeholder="Enter Salestrail API URL">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Webhook Secret</label>
                        <input type="text" name="salestrail_webhook_secret" id="config-salestrail-webhook-secret" class="form-input" placeholder="Enter Salestrail Webhook Secret">
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <button type="button" class="btn btn-logs" onclick="closeConfig()" style="margin-right: 1rem;">Cancel</button>
                        <button type="submit" class="btn-submit">Save Settings</button>
                    </div>
                    <a href="{{ route('bitrix24.start.oauth', $tenant->id ?? 0) }}" id="bitrix-connect-btn" class="btn btn-toggle" style="text-decoration: none; color: white;">Connect to Bitrix24</a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal" id="logs-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="logs-modal-title">Sync Logs</h2>
            <button class="modal-close" onclick="closeLogs()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="logs-list" id="logs-list-container">
                <!-- Log items get injected here -->
            </div>
        </div>
    </div>
</div>

<script>
    function copyWebhook(elementId) {
        const text = document.getElementById(elementId).innerText;
        navigator.clipboard.writeText(text).then(() => {
            const btn = document.querySelector(`#${elementId} + .btn-copy`);
            const oldText = btn.innerText;
            btn.innerText = 'Copied!';
            setTimeout(() => {
                btn.innerText = oldText;
            }, 1500);
        });
    }

    function showLogs(tenantId, companyName) {
        document.getElementById('logs-modal-title').innerText = `Sync Logs for ${companyName}`;
        const container = document.getElementById('logs-list-container');
        container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 2rem;">Loading logs...</div>';
        
        document.getElementById('logs-modal').classList.add('active');

        fetch(`/admin/tenants/${tenantId}/logs`)
            .then(res => res.json())
            .then(logs => {
                if (logs.length === 0) {
                    container.innerHTML = '<div style="text-align: center; color: var(--text-muted); padding: 2rem;">No logs found for this tenant.</div>';
                    return;
                }

                container.innerHTML = logs.map(log => {
                    const date = new Date(log.created_at).toLocaleString();
                    const statusColor = log.status === 'success' || log.status === 'queued' ? 'var(--success)' : 'var(--danger)';
                    return `
                        <div class="log-item">
                            <div class="log-meta">
                                <span>Action: <strong>${log.action}</strong></span>
                                <span>${date}</span>
                            </div>
                            <div class="log-meta" style="margin-bottom: 0.75rem;">
                                <span>Status: <strong style="color: ${statusColor}">${log.status}</strong></span>
                                <span>Call ID: ${log.call_id || 'N/A'}</span>
                            </div>
                            <div class="log-details">
                                <div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.25rem;">Request</div>
                                    <pre class="log-payload">${JSON.stringify(log.request, null, 2)}</pre>
                                </div>
                                <div>
                                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.25rem;">Response</div>
                                    <pre class="log-payload">${JSON.stringify(log.response, null, 2)}</pre>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(err => {
                container.innerHTML = '<div style="text-align: center; color: var(--danger); padding: 2rem;">Failed to fetch logs.</div>';
            });
    }

    function closeLogs() {
        document.getElementById('logs-modal').classList.remove('active');
    }

    function showConfig(tenantId, companyName, clientId, clientSecret, bitrixDomain, salestrailUser, salestrailPassword, salestrailApiKey, salestrailApiUrl, salestrailWebhookSecret) {
        document.getElementById('config-modal-title').innerText = `Configure Integration: ${companyName}`;
        document.getElementById('config-form').action = `/admin/tenants/${tenantId}/update-integrations`;
        document.getElementById('config-bitrix-client-id').value = clientId;
        document.getElementById('config-bitrix-client-secret').value = clientSecret;
        document.getElementById('config-bitrix-domain').value = bitrixDomain;
        document.getElementById('config-salestrail-user').value = salestrailUser;
        document.getElementById('config-salestrail-password').value = salestrailPassword;
        document.getElementById('config-salestrail-api-key').value = salestrailApiKey;
        document.getElementById('config-salestrail-api-url').value = salestrailApiUrl;
        document.getElementById('config-salestrail-webhook-secret').value = salestrailWebhookSecret;
        
        // Update connect button href
        document.getElementById('bitrix-connect-btn').href = `/admin/tenants/${tenantId}/bitrix24/connect`;
        
        document.getElementById('config-modal').classList.add('active');
    }

    function closeConfig() {
        document.getElementById('config-modal').classList.remove('active');
    }

    // Close on click outside modal content
    window.onclick = function(event) {
        const logsModal = document.getElementById('logs-modal');
        const configModal = document.getElementById('config-modal');
        if (event.target === logsModal) {
            closeLogs();
        }
        if (event.target === configModal) {
            closeConfig();
        }
    }
</script>
</body>
</html>
