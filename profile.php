<?php include_once 'inc/header.php'; ?>

<script type='text/javascript' src='js/profile.js'></script>
<script type='text/javascript' src='js/validation.min.js'></script>

<?php
$user = AuthenticationManager::getAuthenticatedUser();

function getRoleInGerman($role){
    switch ($role) {
        case 'admin':
            return 'Administrator';
            break;
        case 'student':
            return 'Schüler';
            break;
        case 'teacher':
            return 'Lehrer';
            break;
        default:
            return 'Unbekannt';
    }
}
?>

<div class='container'>
    <h1>Benutzerprofil</h1>
    <table class='table table-striped'>
        <tr>
            <th>Benutzername</th>
            <td><?php echo escape($user->getUserName()); ?></td>
        </tr>
        <tr>
            <th>Vorname</th>
            <td><?php echo escape($user->getFirstName()); ?></td>
        </tr>
        <tr>
            <th>Nachname</th>
            <td><?php echo escape($user->getLastName()); ?></td>
        </tr>
        <tr>
            <th>Klasse</th>
            <td><?php echo escape($user->getClass()); ?></td>
        </tr>
        <tr>
            <th>Rolle</th>
            <td><?php echo escape(getRoleInGerman($user->getRole())); ?></td>
        </tr>
    </table>
</div>

<div class='container'>
    <h1>Passwort ändern</h1>

    <form id='changeUsersForm'>
        <div class='form-group'>
            <!--<label for='inputPassword'>Passwort</label>-->
            <input type='password' class='form-control' id='inputPassword' name='password' placeholder='Passwort'>
            <input type='hidden' id='inputUserName' name='userName' value='<?php echo escape($user->getUserName()); ?>'>
            <input type='hidden' id='inputUserId' name='userId' value='<?php echo escape($user->getId()); ?>'>
            <input type='hidden' id='inputFirstName' name='firstName' value='<?php echo escape($user->getFirstName()); ?>'>
            <input type='hidden' id='inputLastName' name='lastName' value='<?php echo escape($user->getLastName()); ?>'>
            <input type='hidden' id='inputClass' name='class' value='<?php echo escape($user->getClass()); ?>'>
            <input type='hidden' id='inputType' name='type' value='<?php echo escape($user->getRole()); ?>'>
        </div>
        <button type='submit' class='btn btn-primary' id='btn-edit-user-pwd'>Passwort ändern</button>
    </form>
    <div id='changePasswortChangeMessage'></div>
</div>

<?php include_once 'inc/footer.php'; ?>