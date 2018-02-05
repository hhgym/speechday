<?php
require_once('code/dao/EventDAO.php');
require_once('code/dao/UserDAO.php');
require_once('code/ViewController.php');
include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/book.js'></script>

<p id='pageName' hidden>Book</p>



<div class='container'>
    <div id='tabs-1'>
        <h1>Zeitübersicht</h1>
        <?php if ($user->getRole() === 'student') { ?>
            <h3>Hier können Sie Termine beim gewünschten Lehrer/Lehrerin buchen!<br><br></h3>
        <?php } else { ?>
            <h3>Hier können Sie den Terminen einen Schüler zuteilen!<br><br></h3>
        <?php } ?>
    </div>
</div>

<?php $activeEvent = EventDAO::getActiveEvent(); ?>

<div class='container'>
    <div>
        <?php
            $viewController = ViewController::getInstance();
            echo($viewController->action_getSetSlotsForm());
        ?>
    </div>
</div>


<?php include_once 'inc/footer.php'; ?>

