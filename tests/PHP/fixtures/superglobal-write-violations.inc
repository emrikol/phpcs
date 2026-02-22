<?php
// phpcs:ignore Emrikol.PHP.StrictTypes.MissingStrictTypes -- test fixture

// 1. Direct superglobal assignment (line 5).
$_GET['injected'] = 'malicious';

// 2. POST write (line 8).
$_POST['data'] = 'overwritten';

// 3. REQUEST write (line 11).
$_REQUEST['key'] = 'spoofed';

// 4. SERVER write (line 14).
$_SERVER['HTTP_HOST'] = 'evil.com';

// 5. COOKIE write (line 17).
$_COOKIE['session'] = 'hijacked';

// 6. SESSION write (line 20).
$_SESSION['user'] = 'admin';

// 7. FILES write (line 23).
$_FILES['upload'] = array();

// 8. ENV write (line 26).
$_ENV['PATH'] = '/usr/bin';

// 9. Compound assignment operators (lines 29, 32, 35).
$_GET['count'] += 1;

$_POST['data'] .= 'appended';

$_SERVER['QUERY_STRING'] ??= '';

// 10. Nested key write (line 38).
$_GET['a']['b'] = 'deep';

// 11. Unset superglobal (line 41).
unset( $_GET['key'] );

// 12. Unset multiple including superglobal (line 44).
unset( $normal_var, $_POST['data'] );

// 13. Unset entire superglobal (line 47).
unset( $_REQUEST );

// 14. Whole superglobal assignment (line 50).
$_GET = array();
