<?php
$__siteName = get_setting('site_name', 'Torymail');
$siteLogo = get_setting('site_logo', '');
$baseApi = base_url('api/v1');
?>
<!doctype html>
<html lang="<?= current_lang(); ?>" data-layout="vertical" data-bs-theme="light" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-sidebar-visibility="show" data-layout-width="fluid" data-layout-position="fixed" data-layout-style="default" data-preloader="disable">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>API Documentation — <?= htmlspecialchars($__siteName); ?></title>
    <meta name="csrf-token" content="<?= csrf_token(); ?>">
    <script src="<?= base_url('public/material/assets/js/layout.js'); ?>"></script>
    <link href="<?= base_url('public/material/assets/css/bootstrap.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('public/material/assets/css/icons.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('public/material/assets/css/app.min.css'); ?>" rel="stylesheet">
    <link href="<?= base_url('public/material/assets/css/custom.css'); ?>" rel="stylesheet">
    <script src="<?= base_url('public/js/jquery-3.6.0.js'); ?>"></script>
    <style>
    [data-sidebar-size="sm"] .app-menu{width:70px}
    .app-menu .simplebar-content-wrapper{overflow:hidden}
    .api-card{background:#fff;border:1px solid var(--vz-border-color);border-radius:10px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.04);margin-bottom:20px}
    [data-bs-theme="dark"] .api-card{background:var(--vz-card-bg)}
    .api-card-head{padding:16px 20px;border-bottom:1px solid var(--vz-border-color);display:flex;align-items:center;gap:10px}
    .api-method{font-size:.7rem;font-weight:700;padding:4px 10px;border-radius:6px;letter-spacing:.5px;font-family:monospace}
    .api-method.get{background:rgba(var(--vz-success-rgb),.12);color:var(--vz-success)}
    .api-method.post{background:rgba(var(--vz-primary-rgb),.12);color:var(--vz-primary)}
    .api-method.delete{background:rgba(var(--vz-danger-rgb),.12);color:var(--vz-danger)}
    .api-path{font-family:monospace;font-size:.9rem;font-weight:600}
    .api-card-body{padding:20px}
    .api-card-body p{margin-bottom:12px;color:var(--vz-secondary-color);font-size:.9rem}
    pre.api-code{background:var(--vz-dark);color:#e9ecef;padding:16px;border-radius:8px;font-size:.82rem;overflow-x:auto;margin:0}
    pre.api-code .c{color:#6c757d}
    pre.api-code .k{color:#f7b84b}
    pre.api-code .s{color:#0ab39c}
    pre.api-code .n{color:#299cdb}
    .api-table{font-size:.85rem}
    .api-table th{font-weight:600;white-space:nowrap}
    .api-table code{font-size:.8rem;background:var(--vz-light);padding:1px 6px;border-radius:4px}
    .api-badge{font-size:.65rem;padding:2px 6px;border-radius:4px;font-weight:600}
    .toc a{display:block;padding:6px 0;color:var(--vz-body-color);text-decoration:none;font-size:.88rem;border-bottom:1px solid var(--vz-border-color)}
    .toc a:hover{color:var(--vz-primary)}
    .toc a .badge{float:right}
    </style>
</head>
<body>
<div id="layout-wrapper">

    <!-- TOPBAR -->
    <header id="page-topbar"><div class="layout-width"><div class="navbar-header">
        <div class="d-flex">
            <div class="navbar-brand-box horizontal-logo">
                <a href="<?=base_url();?>" class="logo logo-dark"><span class="logo-sm"><i class="ri-mail-line fs-22 text-primary"></i></span><span class="logo-lg"><i class="ri-mail-line me-1 text-primary"></i> <span class="fw-bold"><?=sanitize($__siteName);?></span></span></a>
                <a href="<?=base_url();?>" class="logo logo-light"><span class="logo-sm"><i class="ri-mail-line fs-22"></i></span><span class="logo-lg"><i class="ri-mail-line me-1"></i> <span class="fw-bold"><?=sanitize($__siteName);?></span></span></a>
            </div>
            <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon"><span class="hamburger-icon"><span></span><span></span><span></span></span></button>
        </div>
        <div class="d-flex align-items-center">
            <div class="ms-1 header-item d-none d-sm-flex"><button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode"><i class="ri-moon-line fs-22"></i></button></div>
            <div class="ms-2 header-item"><a href="<?=base_url();?>" class="btn btn-soft-primary btn-sm"><i class="ri-arrow-left-line me-1"></i>Back</a></div>
        </div>
    </div></div></header>

    <div id="two-column-menu"></div>

    <!-- SIDEBAR -->
    <div class="app-menu navbar-menu">
        <div class="navbar-brand-box">
            <a href="<?=base_url();?>" class="logo logo-dark"><span class="logo-sm"><i class="ri-mail-line fs-22 text-primary"></i></span><span class="logo-lg"><i class="ri-mail-line me-1 text-primary fs-20"></i> <span class="fw-bold fs-16"><?=sanitize($__siteName);?></span></span></a>
            <a href="<?=base_url();?>" class="logo logo-light"><span class="logo-sm"><i class="ri-mail-line fs-22"></i></span><span class="logo-lg"><i class="ri-mail-line me-1 fs-20"></i> <span class="fw-bold fs-16"><?=sanitize($__siteName);?></span></span></a>
            <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover" id="vertical-hover"><i class="ri-record-circle-line"></i></button>
        </div>
        <div id="scrollbar" data-simplebar>
            <div class="container-fluid">
                <ul class="navbar-nav" id="navbar-nav">
                    <li class="menu-title"><span>API v1</span></li>
                    <li class="nav-item"><a href="#overview" class="nav-link menu-link"><i class="ri-book-open-line"></i><span>Overview</span></a></li>
                    <li class="nav-item"><a href="#auth" class="nav-link menu-link"><i class="ri-key-line"></i><span>Authentication</span></a></li>
                    <li class="menu-title"><span>Endpoints</span></li>
                    <li class="nav-item"><a href="#ep-domains" class="nav-link menu-link"><i class="ri-global-line"></i><span>List Domains</span></a></li>
                    <li class="nav-item"><a href="#ep-create" class="nav-link menu-link"><i class="ri-add-circle-line"></i><span>Create Mailbox</span></a></li>
                    <li class="nav-item"><a href="#ep-random" class="nav-link menu-link"><i class="ri-shuffle-line"></i><span>Random Mailbox</span></a></li>
                    <li class="nav-item"><a href="#ep-check" class="nav-link menu-link"><i class="ri-checkbox-circle-line"></i><span>Check Mailbox</span></a></li>
                    <li class="nav-item"><a href="#ep-inbox" class="nav-link menu-link"><i class="ri-inbox-line"></i><span>Get Inbox</span></a></li>
                    <li class="nav-item"><a href="#ep-read" class="nav-link menu-link"><i class="ri-mail-open-line"></i><span>Read Email</span></a></li>
                    <li class="nav-item"><a href="#ep-delete" class="nav-link menu-link"><i class="ri-delete-bin-line"></i><span>Delete Email</span></a></li>
                    <li class="menu-title"><span>Navigation</span></li>
                    <li class="nav-item"><a href="<?=base_url();?>" class="nav-link menu-link"><i class="ri-home-line"></i><span>Home</span></a></li>
                    <li class="nav-item"><a href="<?=base_url('auth/login');?>" class="nav-link menu-link"><i class="ri-login-box-line"></i><span>Login</span></a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="vertical-overlay"></div>

    <!-- MAIN -->
    <div class="main-content">
        <div class="page-content">
            <div class="container-fluid">

                <!-- Header -->
                <div class="mb-4" id="overview">
                    <h3 class="fw-bold mb-1"><i class="ri-code-s-slash-line me-2 text-primary"></i>Temporary Mail API</h3>
                    <p class="text-muted mb-2">Free REST API to create and manage temporary email addresses programmatically.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <span class="badge bg-primary-subtle text-primary">v1.0</span>
                        <span class="badge bg-success-subtle text-success">REST</span>
                        <span class="badge bg-info-subtle text-info">JSON</span>
                        <span class="badge bg-warning-subtle text-warning">CORS Enabled</span>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-12">

                        <!-- Base URL -->
                        <div class="api-card">
                            <div class="api-card-body">
                                <h6 class="fw-semibold mb-2">Base URL</h6>
                                <pre class="api-code"><?= htmlspecialchars($baseApi) ?></pre>
                            </div>
                        </div>

                        <!-- Authentication -->
                        <div id="auth" class="api-card">
                            <div class="api-card-head">
                                <i class="ri-key-line fs-18 text-warning"></i>
                                <h6 class="fw-semibold mb-0">Authentication</h6>
                            </div>
                            <div class="api-card-body">
                                <p>When you create a mailbox, you receive a <code>token</code>. Use this token for all subsequent requests.</p>
                                <p class="fw-semibold mb-2">Two ways to pass the token:</p>
                                <pre class="api-code"><span class="c"># Option 1: Authorization header (recommended)</span>
Authorization: Bearer <span class="s">your-token-here</span>

<span class="c"># Option 2: Query parameter</span>
GET /api/v1/inbox?token=<span class="s">your-token-here</span></pre>
                            </div>
                        </div>

                        <!-- EP: List Domains -->
                        <div id="ep-domains" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method get">GET</span>
                                <span class="api-path">/api/v1/domains</span>
                            </div>
                            <div class="api-card-body">
                                <p>Returns all available shared domains for creating temporary mailboxes. No authentication required.</p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Example Request</h6>
                                <pre class="api-code">curl <?= htmlspecialchars($baseApi) ?>/domains</pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response</h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"domains"</span>: [
    { <span class="k">"id"</span>: <span class="n">1</span>, <span class="k">"domain"</span>: <span class="s">"example.com"</span> },
    { <span class="k">"id"</span>: <span class="n">2</span>, <span class="k">"domain"</span>: <span class="s">"mail.org"</span> }
  ]
}</pre>
                            </div>
                        </div>

                        <!-- EP: Create -->
                        <div id="ep-create" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method post">POST</span>
                                <span class="api-path">/api/v1/create</span>
                            </div>
                            <div class="api-card-body">
                                <p>Create a new temporary mailbox. Returns the email address and API token. If the mailbox already exists, returns the existing token.</p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Parameters</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm api-table">
                                        <thead><tr><th>Name</th><th>Type</th><th>Required</th><th>Description</th></tr></thead>
                                        <tbody>
                                            <tr><td><code>name</code></td><td>string</td><td><span class="badge bg-danger-subtle text-danger api-badge">required</span></td><td>Username part of email (min 3 chars, a-z0-9._-)</td></tr>
                                            <tr><td><code>domain_id</code></td><td>integer</td><td><span class="badge bg-warning-subtle text-warning api-badge">either</span></td><td>Domain ID from /domains endpoint</td></tr>
                                            <tr><td><code>domain</code></td><td>string</td><td><span class="badge bg-warning-subtle text-warning api-badge">either</span></td><td>Domain name (e.g. "example.com")</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Example Request</h6>
                                <pre class="api-code">curl -X POST <?= htmlspecialchars($baseApi) ?>/create \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d <span class="s">'{"name": "john", "domain": "example.com"}'</span></pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response <span class="badge bg-success-subtle text-success">201</span></h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"email"</span>: <span class="s">"john@example.com"</span>,
  <span class="k">"token"</span>: <span class="s">"a1b2c3d4e5f6..."</span>,
  <span class="k">"created_at"</span>: <span class="s">"2026-04-07 12:00:00"</span>
}</pre>
                                <div class="alert alert-warning small mt-3 mb-0"><i class="ri-error-warning-line me-1"></i><strong>Important:</strong> Save the <code>token</code> — you need it for all other API calls on this mailbox. Or use the no-auth endpoints with just the email address.</div>
                            </div>
                        </div>

                        <!-- EP: Random -->
                        <div id="ep-random" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method post">POST</span>
                                <span class="api-path">/api/v1/random</span>
                                <span class="badge bg-success-subtle text-success ms-auto">No Auth</span>
                            </div>
                            <div class="api-card-body">
                                <p>Create a mailbox with a randomly generated username. Optionally specify a domain, otherwise the first available domain is used.</p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Parameters <span class="text-muted fw-normal">(all optional)</span></h6>
                                <div class="table-responsive">
                                    <table class="table table-sm api-table">
                                        <thead><tr><th>Name</th><th>Type</th><th>Description</th></tr></thead>
                                        <tbody>
                                            <tr><td><code>domain_id</code></td><td>integer</td><td>Domain ID (optional)</td></tr>
                                            <tr><td><code>domain</code></td><td>string</td><td>Domain name (optional)</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Example</h6>
                                <pre class="api-code"><span class="c"># No parameters needed — fully automatic</span>
curl -X POST <?= htmlspecialchars($baseApi) ?>/random

<span class="c"># Or specify a domain</span>
curl -X POST <?= htmlspecialchars($baseApi) ?>/random \
  -H <span class="s">"Content-Type: application/json"</span> \
  -d <span class="s">'{"domain": "example.com"}'</span></pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response <span class="badge bg-success-subtle text-success">201</span></h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"email"</span>: <span class="s">"xkwp4827@example.com"</span>,
  <span class="k">"token"</span>: <span class="s">"a1b2c3d4..."</span>,
  <span class="k">"created_at"</span>: <span class="s">"2026-04-07 12:00:00"</span>
}</pre>
                            </div>
                        </div>

                        <!-- EP: Check -->
                        <div id="ep-check" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method get">GET</span>
                                <span class="api-path">/api/v1/check/{email}</span>
                                <span class="badge bg-success-subtle text-success ms-auto">No Auth</span>
                            </div>
                            <div class="api-card-body">
                                <p>Check if a temporary mailbox exists and how many emails it has.</p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Example</h6>
                                <pre class="api-code">curl <?= htmlspecialchars($baseApi) ?>/check/john@example.com</pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response</h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"email"</span>: <span class="s">"john@example.com"</span>,
  <span class="k">"exists"</span>: <span class="n">true</span>,
  <span class="k">"email_count"</span>: <span class="n">3</span>,
  <span class="k">"created_at"</span>: <span class="s">"2026-04-07 12:00:00"</span>
}</pre>
                            </div>
                        </div>

                        <!-- EP: Inbox -->
                        <div id="ep-inbox" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method get">GET</span>
                                <span class="api-path">/api/v1/inbox/{email}</span>
                                <span class="badge bg-success-subtle text-success ms-auto">No Auth</span>
                            </div>
                            <div class="api-card-body">
                                <p>Retrieve all emails in the mailbox inbox. Returns up to 50 most recent emails. <strong>Two ways to access:</strong></p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Example Request</h6>
                                <pre class="api-code"><span class="c"># No auth — just pass email address in URL</span>
curl <?= htmlspecialchars($baseApi) ?>/inbox/john@example.com

<span class="c"># Or with token</span>
curl <?= htmlspecialchars($baseApi) ?>/inbox \
  -H <span class="s">"Authorization: Bearer your-token"</span></pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response</h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"email"</span>: <span class="s">"john@example.com"</span>,
  <span class="k">"count"</span>: <span class="n">2</span>,
  <span class="k">"emails"</span>: [
    {
      <span class="k">"id"</span>: <span class="n">42</span>,
      <span class="k">"from_name"</span>: <span class="s">"GitHub"</span>,
      <span class="k">"from_address"</span>: <span class="s">"noreply@github.com"</span>,
      <span class="k">"subject"</span>: <span class="s">"Verify your email"</span>,
      <span class="k">"is_read"</span>: <span class="n">false</span>,
      <span class="k">"has_attachments"</span>: <span class="n">false</span>,
      <span class="k">"received_at"</span>: <span class="s">"2026-04-07 12:05:00"</span>
    }
  ]
}</pre>
                            </div>
                        </div>

                        <!-- EP: Read -->
                        <div id="ep-read" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method get">GET</span>
                                <span class="api-path">/api/v1/read/{email}/{id}</span>
                                <span class="badge bg-success-subtle text-success ms-auto">No Auth</span>
                            </div>
                            <div class="api-card-body">
                                <p>Read a specific email by ID. Automatically marks the email as read.</p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Example Request</h6>
                                <pre class="api-code"><span class="c"># No auth</span>
curl <?= htmlspecialchars($baseApi) ?>/read/john@example.com/42

<span class="c"># Or with token</span>
curl <?= htmlspecialchars($baseApi) ?>/read/42 \
  -H <span class="s">"Authorization: Bearer your-token"</span></pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response</h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"email"</span>: {
    <span class="k">"id"</span>: <span class="n">42</span>,
    <span class="k">"from_name"</span>: <span class="s">"GitHub"</span>,
    <span class="k">"from_address"</span>: <span class="s">"noreply@github.com"</span>,
    <span class="k">"subject"</span>: <span class="s">"Verify your email"</span>,
    <span class="k">"body_text"</span>: <span class="s">"Click here to verify..."</span>,
    <span class="k">"body_html"</span>: <span class="s">"&lt;p&gt;Click here...&lt;/p&gt;"</span>,
    <span class="k">"attachments"</span>: [],
    <span class="k">"received_at"</span>: <span class="s">"2026-04-07 12:05:00"</span>
  }
}</pre>
                            </div>
                        </div>

                        <!-- EP: Delete -->
                        <div id="ep-delete" class="api-card">
                            <div class="api-card-head">
                                <span class="api-method delete">DELETE</span>
                                <span class="api-path">/api/v1/delete/{email}/{id}</span>
                                <span class="badge bg-success-subtle text-success ms-auto">No Auth</span>
                            </div>
                            <div class="api-card-body">
                                <p>Delete a specific email by ID. This action is permanent.</p>
                                <h6 class="fw-semibold small text-uppercase text-muted mb-2">Example Request</h6>
                                <pre class="api-code"><span class="c"># No auth</span>
curl -X DELETE <?= htmlspecialchars($baseApi) ?>/delete/john@example.com/42

<span class="c"># Or with token</span>
curl -X DELETE <?= htmlspecialchars($baseApi) ?>/delete/42 \
  -H <span class="s">"Authorization: Bearer your-token"</span></pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Response</h6>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">true</span>,
  <span class="k">"message"</span>: <span class="s">"Email deleted"</span>
}</pre>
                            </div>
                        </div>

                        <!-- Error Format -->
                        <div class="api-card">
                            <div class="api-card-head">
                                <i class="ri-error-warning-line fs-18 text-danger"></i>
                                <h6 class="fw-semibold mb-0">Error Responses</h6>
                            </div>
                            <div class="api-card-body">
                                <p>All errors return the following format with an appropriate HTTP status code:</p>
                                <pre class="api-code">{
  <span class="k">"success"</span>: <span class="n">false</span>,
  <span class="k">"error"</span>: <span class="s">"Description of what went wrong"</span>
}</pre>
                                <h6 class="fw-semibold small text-uppercase text-muted mt-3 mb-2">Common Status Codes</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm api-table">
                                        <tbody>
                                            <tr><td><code>400</code></td><td>Bad request — missing or invalid parameters</td></tr>
                                            <tr><td><code>401</code></td><td>Unauthorized — missing or invalid token</td></tr>
                                            <tr><td><code>404</code></td><td>Not found — email or endpoint doesn't exist</td></tr>
                                            <tr><td><code>405</code></td><td>Method not allowed — wrong HTTP method</td></tr>
                                            <tr><td><code>429</code></td><td>Rate limited — too many requests</td></tr>
                                            <tr><td><code>500</code></td><td>Server error</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Start -->
                        <div class="api-card">
                            <div class="api-card-head">
                                <i class="ri-rocket-line fs-18 text-primary"></i>
                                <h6 class="fw-semibold mb-0">Quick Start — Python Example</h6>
                            </div>
                            <div class="api-card-body">
                                <pre class="api-code"><span class="c">import</span> requests, time

API = <span class="s">"<?= htmlspecialchars($baseApi) ?>"</span>

<span class="c"># 1. Get available domains</span>
domains = requests.get(f<span class="s">"{API}/domains"</span>).json()[<span class="s">"domains"</span>]
print(<span class="s">"Domains:"</span>, [d[<span class="s">"domain"</span>] <span class="c">for</span> d <span class="c">in</span> domains])

<span class="c"># 2. Create a temp mailbox</span>
r = requests.post(f<span class="s">"{API}/create"</span>, json={
    <span class="s">"name"</span>: <span class="s">"mytest"</span>,
    <span class="s">"domain"</span>: domains[<span class="n">0</span>][<span class="s">"domain"</span>]
}).json()
token = r[<span class="s">"token"</span>]
print(f<span class="s">"Email: {r['email']}"</span>)

<span class="c"># 3. Poll for new emails</span>
headers = {<span class="s">"Authorization"</span>: f<span class="s">"Bearer {token}"</span>}
<span class="c">while True:</span>
    inbox = requests.get(f<span class="s">"{API}/inbox"</span>, headers=headers).json()
    <span class="c">if</span> inbox[<span class="s">"count"</span>] > <span class="n">0</span>:
        email_id = inbox[<span class="s">"emails"</span>][<span class="n">0</span>][<span class="s">"id"</span>]
        email = requests.get(f<span class="s">"{API}/read/{email_id}"</span>, headers=headers).json()
        print(f<span class="s">"Subject: {email['email']['subject']}"</span>)
        <span class="c">break</span>
    time.sleep(<span class="n">5</span>)</pre>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <footer class="footer"><div class="container-fluid"><div class="row"><div class="col-sm-6"><script>document.write(new Date().getFullYear())</script> &copy; <?=htmlspecialchars($__siteName);?></div><div class="col-sm-6"><div class="text-sm-end d-none d-sm-block">API Documentation</div></div></div></div></footer>
    </div>
</div>

<script src="<?=base_url('public/material/assets/libs/bootstrap/js/bootstrap.bundle.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/libs/simplebar/simplebar.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/libs/node-waves/waves.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/libs/feather-icons/feather.min.js');?>"></script>
<script src="<?=base_url('public/material/assets/js/plugins.js');?>"></script>
<script src="<?=base_url('public/material/assets/js/app.js');?>"></script>
<script>
(function(){var e=document.getElementById('scrollbar');if(e&&typeof SimpleBar!=='undefined'&&!e.SimpleBar){e.classList.add('h-100');new SimpleBar(e)}})();
</script>
</body>
</html>
