<?
# require('fogado.inc');

# include("user.class");
# $USER = new User();

# Head("Fogad��ra - " . $USER->dnev);

$username = 'hottentotta';
$password = 'mutter';

if (pam_auth($username, $password, &$error)) {
        echo "Yeah baby, we're authenticated!";
} else {
        echo $error;
}
?>

