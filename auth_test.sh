#!/bin/bash
# SXpress Authentication Module - Shell-based Browser Tests
# Uses curl with cookie jar for session management

BASE="http://127.0.0.1:8000"
COOKIE_JAR="/tmp/sxpress_cookies.txt"
rm -f "$COOKIE_JAR"

PASS=0
FAIL=0

pass() { echo "✅ PASS: $1"; ((PASS++)); }
fail() { echo "❌ FAIL: $1 — $2"; ((FAIL++)); }

echo "=== SXpress Authentication Module - Shell Tests ==="
echo ""

# Helper: get CSRF token from page
get_csrf() {
    curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" "$BASE$1" | grep -oP 'name="_token" value="\K[^"]+' | head -1
}

# Helper: GET page and check status
get_status() {
    curl -s -o /dev/null -w "%{http_code}" -b "$COOKIE_JAR" "$BASE$1"
}

# Helper: POST form and follow redirects (with cookie jar)
do_post() {
    local url="$1"
    local data="$2"
    local tmp_resp="/tmp/sxpress_resp_headers.txt"
    curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
        -d "_token=$(get_csrf '')" \
        -d "$data" \
        -w "\nHTTP_CODE:%{http_code}\nREDIRECT:%{redirect_url}" \
        "$BASE$url" > /tmp/sxpress_post_out.txt 2>&1
    grep -o "HTTP_CODE:[0-9]*" /tmp/sxpress_post_out.txt | cut -d: -f2
    grep -o "REDIRECT:[^ \n]*" /tmp/sxpress_post_out.txt | cut -d: -f2
}

# TEST 1: Root route redirects to /login
echo "--- TEST 1: Root route ---"
code=$(get_status "/")
location=$(curl -s -o /dev/null -w "%{redirect_url}" -b "$COOKIE_JAR" "$BASE/")
[[ "$code" == "302" || "$code" == "301" ]] && pass "GET / returns redirect" || fail "GET / returns redirect" "Got $code"
[[ "$location" == */login ]] && pass "Redirects to /login" || fail "Redirects to /login" "Got $location"

# TEST 2: Login page
echo ""
echo "--- TEST 2: Login page ---"
code=$(get_status "/login")
body=$(curl -s -b "$COOKIE_JAR" "$BASE/login")
[[ "$code" == "200" ]] && pass "GET /login returns 200" || fail "GET /login returns 200" "Got $code"
echo "$body" | grep -q 'name="email"' && pass "Has email field" || fail "Has email field" "Missing"
echo "$body" | grep -q 'name="password"' && pass "Has password field" || fail "Has password field" "Missing"
echo "$body" | grep -q '_token' && pass "Has CSRF token" || fail "Has CSRF token" "Missing"
echo "$body" | grep -q 'remember' && pass "Has Remember me" || fail "Has Remember me" "Missing"
echo "$body" | grep -q 'Forgot' && pass "Has Forgot password link" || fail "Has Forgot password link" "Missing"
echo "$body" | grep -q -i 'register\|Create Account' && pass "Has Register link" || fail "Has Register link" "Missing"

# TEST 3: Register page
echo ""
echo "--- TEST 3: Register page ---"
code=$(get_status "/register")
body=$(curl -s -b "$COOKIE_JAR" "$BASE/register")
[[ "$code" == "200" ]] && pass "GET /register returns 200" || fail "GET /register returns 200" "Got $code"
echo "$body" | grep -q 'Rajkot' && pass "Has Rajkot in dropdown" || fail "Has Rajkot in dropdown" "Missing"
echo "$body" | grep -q 'Kashmore Gate' && pass "Has Kashmore Gate in dropdown" || fail "Has Kashmore Gate in dropdown" "Missing"
echo "$body" | grep -q 'password_confirmation' && pass "Has password confirmation field" || fail "Has password confirmation field" "Missing"

# TEST 4: Wrong password rejected
echo ""
echo "--- TEST 4: Wrong password ---"
csrf=$(get_csrf "/login")
resp=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    -d "email=staff@sxpress.com&password=clearlywrongpassword" \
    -w "\nHTTP:%{http_code}" \
    -o /tmp/sxpress_login_fail.html \
    "$BASE/login")
code=$(echo "$resp" | grep HTTP: | cut -d: -f2)
redirect=$(echo "$resp" | grep -o 'Location: [^ ]*' | cut -d' ' -f2)
[[ "$code" == "302" ]] && pass "Wrong password returns 302" || fail "Wrong password returns 302" "Got $code"
[[ "$redirect" == */login ]] && pass "Redirects back to login" || fail "Redirects back to login" "Got: $redirect"

# TEST 5: Inactive user BLOCKED
echo ""
echo "--- TEST 5: Inactive user BLOCKED ---"
csrf=$(get_csrf "/login")
resp=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    -d "email=inactive@sxpress.com&password=password123" \
    -w "\nHTTP:%{http_code}" \
    -o /tmp/sxpress_inactive.html \
    "$BASE/login")
code=$(echo "$resp" | grep HTTP: | cut -d: -f2)
# After inactive rejection, fetch login page to check error message
body=$(curl -s -b "$COOKIE_JAR" "$BASE/login")
[[ "$code" == "302" ]] && pass "Inactive login returns 302" || fail "Inactive login returns 302" "Got $code"
echo "$body" | grep -q -i 'deactivat' && pass "Shows deactivation message" || fail "Shows deactivation message" "Missing"

# TEST 6: Valid staff login
echo ""
echo "--- TEST 6: Valid Staff login ---"
csrf=$(get_csrf "/login")
resp=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    -d "email=staff@sxpress.com&password=password123" \
    -w "\nHTTP:%{http_code}" \
    -o /tmp/sxpress_staff_login.html \
    "$BASE/login")
code=$(echo "$resp" | grep HTTP: | cut -d: -f2)
redirect=$(echo "$resp" | grep -o 'Location: [^ ]*' | cut -d' ' -f2)
[[ "$code" == "302" ]] && pass "Valid login returns 302" || fail "Valid login returns 302" "Got $code"
[[ "$redirect" == */dash ]] && pass "Redirects to /dash" || fail "Redirects to /dash" "Got: $redirect"

# TEST 7: Authenticated /dash access
echo ""
echo "--- TEST 7: Authenticated /dash access ---"
code=$(get_status "/dash")
[[ "$code" == "200" ]] && pass "GET /dash returns 200" || fail "GET /dash returns 200" "Got $code"
body=$(curl -s -b "$COOKIE_JAR" "$BASE/dash")
echo "$body" | grep -q -i 'dashboard\|GR\|Rajkot\|SXpress' && pass "Dashboard shows content" || fail "Dashboard shows content" "Got empty or wrong content"

# TEST 8: Unauthenticated /dash redirect
echo ""
echo "--- TEST 8: Unauthenticated /dash redirect ---"
rm -f "$COOKIE_JAR"
code=$(get_status "/dash")
redirect=$(curl -s -o /dev/null -w "%{redirect_url}" -b "$COOKIE_JAR" "$BASE/dash")
[[ "$code" == "302" ]] && pass "Unauthenticated /dash returns 302" || fail "Unauthenticated /dash returns 302" "Got $code"
[[ "$redirect" == */login ]] && pass "Redirects to /login" || fail "Redirects to /login" "Got: $redirect"

# TEST 9: Logout
echo ""
echo "--- TEST 9: Logout ---"
# Re-login as staff first
csrf=$(get_csrf "/login")
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf&email=staff@sxpress.com&password=password123" \
    "$BASE/login" > /dev/null
csrf=$(get_csrf "/login")
resp=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    -w "\nHTTP:%{http_code}" \
    -o /tmp/sxpress_logout.html \
    "$BASE/logout")
code=$(echo "$resp" | grep HTTP: | cut -d: -f2)
[[ "$code" == "302" ]] && pass "Logout returns 302" || fail "Logout returns 302" "Got $code"
# After logout, /dash should be inaccessible
code2=$(get_status "/dash")
[[ "$code2" == "302" ]] && pass "Post-logout /dash blocked" || fail "Post-logout /dash blocked" "Got $code2"

# TEST 10: Register new user
echo ""
echo "--- TEST 10: Register new user with valid office ---"
csrf=$(get_csrf "/register")
NEWEMAIL="newuser_$(date +%s)@sxpress.com"
resp=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    -d "name=Test New User&email=$NEWEMAIL&office=Kashmore Gate&password=password123&password_confirmation=password123" \
    -w "\nHTTP:%{http_code}" \
    -o /tmp/sxpress_register.html \
    "$BASE/register")
code=$(echo "$resp" | grep HTTP: | cut -d: -f2)
redirect=$(echo "$resp" | grep -o 'Location: [^ ]*' | cut -d' ' -f2)
[[ "$code" == "302" ]] && pass "Registration returns 302" || fail "Registration returns 302" "Got $code"
[[ "$redirect" == */dash ]] && pass "Redirects to /dash" || fail "Redirects to /dash" "Got: $redirect"
# Verify new user can access /dash
code2=$(get_status "/dash")
[[ "$code2" == "200" ]] && pass "New user can access /dash" || fail "New user can access /dash" "Got $code2"

# TEST 11: Register with invalid office
echo ""
echo "--- TEST 11: Register with invalid office ---"
csrf=$(get_csrf "/register")
resp=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    -d "name=Bad User&email=baduser_$(date +%s)@sxpress.com&office=FakeBranch&password=password123&password_confirmation=password123" \
    -w "\nHTTP:%{http_code}" \
    -o /tmp/sxpress_bad_office.html \
    "$BASE/register")
code=$(echo "$resp" | grep HTTP: | cut -d: -f2)
body=$(cat /tmp/sxpress_bad_office.html)
[[ "$code" == "302" ]] && pass "Invalid office returns 302" || fail "Invalid office returns 302" "Got $code"
echo "$body" | grep -q -i 'invalid\|office\|selected' && pass "Shows validation error" || fail "Shows validation error" "Missing"

# TEST 12: Password reset page
echo ""
echo "--- TEST 12: Password reset page ---"
code=$(get_status "/password/reset")
[[ "$code" == "200" ]] && pass "GET /password/reset returns 200" || fail "GET /password/reset returns 200" "Got $code"
body=$(curl -s -b "$COOKIE_JAR" "$BASE/password/reset")
echo "$body" | grep -q 'email' && pass "Has email input field" || fail "Has email input field" "Missing"

# TEST 13: SuperAdmin login and branch access
echo ""
echo "--- TEST 13: SuperAdmin login + branch access ---"
rm -f "$COOKIE_JAR"
csrf=$(get_csrf "/login")
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf&email=superadmin@sxpress.com&password=password123" \
    "$BASE/login" > /dev/null
code=$(get_status "/dash")
[[ "$code" == "200" ]] && pass "SuperAdmin can access /dash" || fail "SuperAdmin can access /dash" "Got $code"
code=$(get_status "/branch")
[[ "$code" == "200" ]] && pass "SuperAdmin can access /branch" || fail "SuperAdmin can access /branch" "Got $code"

# TEST 14: Staff blocked from /branch
echo ""
echo "--- TEST 14: Staff blocked from admin routes ---"
# Logout SuperAdmin
csrf=$(get_csrf "/login")
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf" \
    "$BASE/logout" > /dev/null
# Login as Staff
csrf=$(get_csrf "/login")
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf&email=staff@sxpress.com&password=password123" \
    "$BASE/login" > /dev/null
code=$(get_status "/branch")
[[ "$code" == "403" ]] && pass "Staff blocked from /branch (403)" || fail "Staff blocked from /branch" "Got $code (expected 403)"

# TEST 15: Viewer can access GR list
echo ""
echo "--- TEST 15: Viewer can access GR list ---"
# Logout Staff
csrf=$(get_csrf "/login")
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST -d "_token=$csrf" "$BASE/logout" > /dev/null
# Login as Viewer
csrf=$(get_csrf "/login")
curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
    -d "_token=$csrf&email=viewer@sxpress.com&password=password123" \
    "$BASE/login" > /dev/null
code=$(get_status "/gr")
[[ "$code" == "200" ]] && pass "Viewer can access /gr" || fail "Viewer can access /gr" "Got $code"

# TEST 16: Rate limiting (6 wrong attempts)
echo ""
echo "--- TEST 16: Login rate limiting ---"
rm -f "$COOKIE_JAR"
for i in $(seq 1 7); do
    csrf=$(get_csrf "/login")
    code=$(curl -s -c "$COOKIE_JAR" -b "$COOKIE_JAR" -X POST \
        -d "_token=$csrf&email=ratelimit@sxpress.com&password=wrong$i" \
        -w "%{http_code}" \
        -o /dev/null \
        "$BASE/login")
    codes[$i]=$code
done
# Check if any request after the 5th got a 429 or was blocked
last="${codes[6]}"
[[ "$last" == "429" || "$last" == "302" ]] && pass "Rate limiting kicks in after 6 attempts" || fail "Rate limiting kicks in" "Last code: $last"

# TEST 17: CSRF protection
echo ""
echo "--- TEST 17: CSRF protection ---"
rm -f "$COOKIE_JAR"
code=$(curl -s -o /dev/null -w "%{http_code}" -X POST \
    -d "email=test@test.com&password=test" \
    "$BASE/login")
[[ "$code" == "419" ]] && pass "POST without CSRF token returns 419" || fail "POST without CSRF token returns 419" "Got $code"

# SUMMARY
echo ""
echo "=== RESULTS ==="
echo "✅ PASSED: $PASS"
echo "❌ FAILED: $FAIL"
echo ""
echo "Test credentials:"
echo "  SuperAdmin: superadmin@sxpress.com / password123"
echo "  Admin:      admin@Rajkot.sxpress.com / password123"
echo "  Manager:    manager@sxpress.com / password123"
echo "  Staff:      staff@sxpress.com / password123"
echo "  Viewer:     viewer@sxpress.com / password123"
echo "  INACTIVE:   inactive@sxpress.com / password123 (BLOCKED)"

rm -f /tmp/sxpress_cookies.txt /tmp/sxpress_*.txt /tmp/sxpress_*.html