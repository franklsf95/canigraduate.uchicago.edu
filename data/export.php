<?php
include('../include/curl.php');

$postdata = file_get_contents("php://input");
$request = json_decode($postdata);

$cookie_file = '/tmp/cookies' . rand();

// wait until the tunnel is established, may take non-negligible time.
while(get('https://classes.uchicago.edu/', $cookie_file) === false);

get('https://classes.uchicago.edu/loggedin/login.php', $cookie_file);
$saml_intermediary = post('https://shibboleth2.uchicago.edu/idp/profile/SAML2/Redirect/SSO?execution=e1s1', array(
	'j_username' => $_POST['username'],
	'j_password' => $_POST['password'],
	'_eventId_proceed' => true
), $cookie_file);
preg_match_all('/name="(.+?)" value="(.+?)"/', $saml_intermediary, $matches);
if(sizeof($matches[0]) == 0) {
	http_response_code(400);
	die('Authentication failed.');
}
post('https://classes.uchicago.edu/Shibboleth.sso/SAML2/POST', array(
	'RelayState' => html_entity_decode($matches[2][0]),
	'SAMLResponse' => html_entity_decode($matches[2][1])
), $cookie_file);

// authenticated, then "agree" to terms
post('https://classes.uchicago.edu/loggedin/agreeToTerms.php', array(
	'submit' => 'I Agree' // lol
), $cookie_file);
header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: inline; filename=schedule.ics');
sleep(1);
post('https://classes.uchicago.edu/loggedin/myCourses.php', array('TermName' => $_POST['quarter']), $cookie_file);
sleep(1);
echo post('https://classes.uchicago.edu/loggedin/exportMyCourses.php', array('TermName' => $_POST['quarter']), $cookie_file);
unlink($cookie_file);
