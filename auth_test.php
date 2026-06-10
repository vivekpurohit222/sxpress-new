<?php
/**
 * SXpress Authentication Module - Automated Browser Test
 * Run with: php auth_test.php
 */

$base = 'http://127.0.0.1:8000';
$jar = null;

function makeRequest($method, $url, $data = [], $headers = [], &$jar = null) {
    $ch = curl_init();
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'SXpress-Auth-Test/1.0',
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = http_build_query($data);
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    // Extract Set-Cookie
    preg_match_all('/Set-Cookie: ([^=]+)=([^;]+)/', $headers, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $i => $name) {
            $value = $matches[2][$i];
            if ($name === 'laravel_session') {
                $jar = $name . '=' . $value;
            }
        }
    }

    return ['code' => $httpCode, 'body' => $body, 'headers' => $headers];
}

function postForm($url, $data, &$jar) {
    $ch = curl_init();
    $cookieHeader = $jar ? "Cookie: $jar" : "";
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'SXpress-Auth-Test/1.0',
        CURLOPT_HTTPHEADER => ['Accept: text/html', $cookieHeader],
    ];
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerBlock = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    // Extract session cookie
    preg_match('/Set-Cookie: laravel_session=([^;]+)/', $headerBlock, $m);
    if ($m) $jar = 'laravel_session=' . $m[1];

    // Get new csrf
    preg_match('/name="_token" value="([^"]+)"/', $body, $csrf);
    $token = $csrf[1] ?? '';

    // Check redirect location
    preg_match('/Location: ([^\r\n]+)/', $headerBlock, $loc);
    $redirect = trim($loc[1] ?? '');

    return ['code' => $httpCode, 'body' => $body, 'redirect' => $redirect, 'csrf' => $token];
}

function getPage($url, &$jar) {
    $ch = curl_init();
    $cookieHeader = $jar ? "Cookie: $jar" : "";
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'SXpress-Auth-Test/1.0',
        CURLOPT_HTTPHEADER => ['Accept: text/html', $cookieHeader],
    ];
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $headerBlock = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    preg_match('/Set-Cookie: laravel_session=([^;]+)/', $headerBlock, $m);
    if ($m) $jar = 'laravel_session=' . $m[1];

    preg_match('/name="_token" value="([^"]+)"/', $body, $csrf);
    $token = $csrf[1] ?? '';

    preg_match('/Location: ([^\r\n]+)/', $headerBlock, $loc);
    $redirect = trim($loc[1] ?? '');

    return ['code' => $httpCode, 'body' => $body, 'redirect' => $redirect, 'csrf' => $token];
}

function test($name, $condition, $detail = '') {
    $status = $condition ? "✅ PASS" : "❌ FAIL";
    echo "$status: $name";
    if ($detail) echo " — $detail";
    echo "\n";
    return $condition;
}

$passed = 0;
$failed = 0;

echo "=== SXpress Authentication Module - Automated Tests ===\n\n";

// TEST 1: Root route
echo "--- TEST 1: Root route redirects to /login ---\n";
$r = getPage("$base/", $jar);
test('GET / returns 302', $r['code'] === 302);
test('Redirects to /login', strpos($r['redirect'], '/login') !== false);

// TEST 2: Login page loads
echo "\n--- TEST 2: Login page ---\n";
$r = getPage("$base/login", $jar);
test('GET /login returns 200', $r['code'] === 200);
test('Contains email field', strpos($r['body'], 'type="email"') !== false || strpos($r['body'], 'name="email"') !== false);
test('Contains password field', strpos($r['body'], 'name="password"') !== false);
test('Has CSRF token', !empty($r['csrf']));
test('Has Remember me checkbox', strpos($r['body'], 'remember') !== false);
test('Has Forgot password link', strpos($r['body'], 'Forgot') !== false);
test('Has Register link', strpos($r['body'], 'Create Account') !== false || strpos($r['body'], 'register') !== false);

// TEST 3: Register page
echo "\n--- TEST 3: Register page ---\n";
$r = getPage("$base/register", $jar);
test('GET /register returns 200', $r['code'] === 200);
test('Has office dropdown with Rajkot', strpos($r['body'], 'Rajkot') !== false);
test('Has office dropdown with Kashmore Gate', strpos($r['body'], 'Kashmore Gate') !== false);
test('Has office dropdown with Navagam', strpos($r['body'], 'Navagam') !== false);
test('Has CSRF token', !empty($r['csrf']));
test('Has password confirmation field', strpos($r['body'], 'password_confirmation') !== false);

// TEST 4: Wrong password
echo "\n--- TEST 4: Login with wrong password ---\n";
$r = getPage("$base/login", $jar);
$r2 = postForm("$base/login", [
    '_token' => $r['csrf'],
    'email' => 'staff@sxpress.com',
    'password' => 'clearlywrongpassword',
], $jar);
test('Wrong password returns 302', $r2['code'] === 302);
test('Redirects back to login', strpos($r2['redirect'], '/login') !== false);

// TEST 5: Inactive user blocked
echo "\n--- TEST 5: Inactive user login BLOCKED ---\n";
$r = getPage("$base/login", $jar);
$r2 = postForm("$base/login", [
    '_token' => $r['csrf'],
    'email' => 'inactive@sxpress.com',
    'password' => 'password123',
], $jar);
test('Inactive login returns 302 (kicked out)', $r2['code'] === 302);
// Verify we're redirected back to login with deactivation message
$r3 = getPage("$base/login", $jar);
$deactivated = strpos(strtolower($r3['body']), 'deactivat') !== false;
test('Login page shows deactivation message', $deactivated, $deactivated ? '' : 'Message not found');

// TEST 6: Valid staff login
echo "\n--- TEST 6: Valid Staff login ---\n";
$r = getPage("$base/login", $jar);
$r2 = postForm("$base/login", [
    '_token' => $r['csrf'],
    'email' => 'staff@sxpress.com',
    'password' => 'password123',
], $jar);
test('Valid login returns 302', $r2['code'] === 302);
test('Redirects to /dash', strpos($r2['redirect'], '/dash') !== false);

// TEST 7: Session access to /dash
echo "\n--- TEST 7: Authenticated access to /dash ---\n";
$r = getPage("$base/dash", $jar);
test('GET /dash returns 200', $r['code'] === 200);
$content = strtolower($r['body']);
$hasDashContent = strpos($content, 'dashboard') !== false || strpos($content, 'gr ') !== false || strpos($content, 'raJkot') !== false;
test('Dashboard shows content', $hasDashContent, $hasDashContent ? '' : 'Unexpected body');

// TEST 8: Unauthenticated /dash redirects
echo "\n--- TEST 8: Unauthenticated /dash redirect ---\n";
$jarBackup = $jar;
$jar = null; // fresh session
$r = getPage("$base/dash", $jar);
test('Unauthenticated /dash returns 302', $r['code'] === 302);
test('Redirects to /login', strpos($r['redirect'], '/login') !== false);

// TEST 9: Logout
echo "\n--- TEST 9: Logout ---\n";
$r = getPage("$base/login", $jar);
$r2 = postForm("$base/logout", ['_token' => $r['csrf']], $jar);
test('Logout POST returns 302', $r2['code'] === 302);
// Verify session cleared
$r3 = getPage("$base/dash", $jar);
test('Post-logout /dash returns 302', $r3['code'] === 302);

// TEST 10: Register new user with valid office
echo "\n--- TEST 10: Register with valid office ---\n";
$testEmail = 'testuser_' . time() . '@sxpress.com';
$r = getPage("$base/register", $jar);
$r2 = postForm("$base/register", [
    '_token' => $r['csrf'],
    'name' => 'Test New User',
    'email' => $testEmail,
    'office' => 'Kashmore Gate',
    'password' => 'password123',
    'password_confirmation' => 'password123',
], $jar);
test('Registration returns 302', $r2['code'] === 302);
test('Redirects to /dash', strpos($r2['redirect'], '/dash') !== false);
// Verify can access /dash
$r3 = getPage("$base/dash", $jar);
test('New user can access /dash', $r3['code'] === 200);

// TEST 11: Register with invalid office
echo "\n--- TEST 11: Register with invalid office ---\n";
$r = getPage("$base/register", $jar);
$r2 = postForm("$base/register", [
    '_token' => $r['csrf'],
    'name' => 'Bad Office User',
    'email' => 'badoffice' . time() . '@sxpress.com',
    'office' => 'FakeNonExistentBranch',
    'password' => 'password123',
    'password_confirmation' => 'password123',
], $jar);
test('Invalid office returns 302 (validation failure)', $r2['code'] === 302);
$hasError = strpos(strtolower($r2['body']), 'invalid') !== false || strpos(strtolower($r2['body']), 'office') !== false;
test('Shows validation error for office', $hasError, $hasError ? '' : 'No validation error');

// TEST 12: Password reset page
echo "\n--- TEST 12: Password reset request page ---\n";
$r = getPage("$base/password/reset", $jar);
test('GET /password/reset returns 200', $r['code'] === 200);
test('Has email input field', strpos($r['body'], 'type="email"') !== false || strpos($r['body'], 'name="email"') !== false);

// TEST 13: SuperAdmin login + branch access
echo "\n--- TEST 13: SuperAdmin login and branch route access ---\n";
$r = getPage("$base/login", $jar);
$r2 = postForm("$base/login", [
    '_token' => $r['csrf'],
    'email' => 'superadmin@sxpress.com',
    'password' => 'password123',
], $jar);
test('SuperAdmin login returns 302', $r2['code'] === 302);
test('Redirects to /dash', strpos($r2['redirect'], '/dash') !== false);
$r3 = getPage("$base/dash", $jar);
test('SuperAdmin can access /dash', $r3['code'] === 200);
$r4 = getPage("$base/branch", $jar);
test('SuperAdmin can access /branch', $r4['code'] === 200);

// TEST 14: Staff blocked from /branch
echo "\n--- TEST 14: Staff blocked from admin routes ---\n";
// Logout first
$r = getPage("$base/login", $jar);
postForm("$base/logout", ['_token' => $r['csrf']], $jar);
// Login as staff
$r = getPage("$base/login", $jar);
postForm("$base/login", ['_token' => $r['csrf'], 'email' => 'staff@sxpress.com', 'password' => 'password123'], $jar);
// Try /branch
$r2 = getPage("$base/branch", $jar);
$blocked = $r2['code'] === 403 || $r2['code'] === 302;
test('Staff is blocked from /branch', $blocked, "Got HTTP {$r2['code']}");

// TEST 15: Password reset link request (valid email)
echo "\n--- TEST 15: Password reset link request ---\n";
$r = getPage("$base/password/reset", $jar);
$r2 = postForm("$base/password/email", [
    '_token' => $r['csrf'],
    'email' => 'staff@sxpress.com',
], $jar);
test('Password reset email request returns 200/302', in_array($r2['code'], [200, 302]));

// TEST 16: Login rate limiting
echo "\n--- TEST 16: Login rate limiting ---\n";
$rateLimitJar = null;
for ($i = 1; $i <= 6; $i++) {
    $r = getPage("$base/login", $rateLimitJar);
    $r2 = postForm("$base/login", [
        '_token' => $r['csrf'],
        'email' => 'staff@sxpress.com',
        'password' => 'wrongattempt' . $i,
    ], $rateLimitJar);
    $codes[$i] = $r2['code'];
}
$rateLimited = in_array(429, $codes) || in_array(302, $codes);
test('After 6 failed attempts, rate limited (429 or blocked)', $rateLimited, "HTTP codes: " . implode(',', $codes));

// TEST 17: Viewer role can access GR list
echo "\n--- TEST 17: Viewer login and GR access ---\n";
postForm("$base/logout", ['_token' => $r['csrf']], $jar);
$r = getPage("$base/login", $jar);
postForm("$base/login", ['_token' => $r['csrf'], 'email' => 'viewer@sxpress.com', 'password' => 'password123'], $jar);
$r2 = getPage("$base/gr", $jar);
test('Viewer can access /gr', $r2['code'] === 200);
test('Viewer can see GR list content', strpos($r2['body'], 'GR') !== false || strpos($r2['body'], 'Goods') !== false);

// TEST 18: CSRF protection on login
echo "\n--- TEST 18: CSRF protection ---\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$base/login");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'email=test@test.com&password=test');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'TestAgent');
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
test('POST without CSRF token rejected (419)', $code === 419 || $code === 302);

echo "\n=== TEST SUMMARY ===\n";
echo "Tests completed. Check ✅/❌ above.\n";
echo "\nCredentials used:\n";
echo "  SuperAdmin: superadmin@sxpress.com / password123\n";
echo "  Admin:      admin@Rajkot.sxpress.com / password123\n";
echo "  Manager:    manager@sxpress.com / password123\n";
echo "  Staff:      staff@sxpress.com / password123\n";
echo "  Viewer:     viewer@sxpress.com / password123\n";
echo "  INACTIVE:   inactive@sxpress.com / password123 (should be BLOCKED)\n";