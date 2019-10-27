<?php
require_once('code/dao/EventDAO.php');
require_once('code/ViewController.php');
require_once('code/AuthenticationManager.php');
AuthenticationManager::checkPrivilege('teacher');

include_once 'inc/header.php';
?>

<script type='text/javascript' src='js/teacher.js'></script>

<p id='pageName' hidden>Teacher</p>

<div class='container'>

    <h1>Meine Übersicht</h1>

    <div class='panel-group' id='accordion'>
<!--
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h4 class='panel-title'>
                    <a data-toggle='collapse' data-parent='#accordion' href='#timetableview'>
                        Mein Terminplan
                    </a>
                </h4>
            </div>
            <div id='timetableview' class='panel-collapse collapse in'>
                <div class='panel-body'>
                    <form id='chooseMySlotsForm'>
                        <div class='form-group'>
                            <label for='selectType'>Darstellungstyp</label>
                            <select class='form-control' id='selectType' name='type'>
                                <option value='1'>Kompakt</option>
                                <option value='2'>Vollständig</option>
                            </select>
                        </div>
                    </form>

                    <input type='hidden' id='schoolUrl' value="<?php echo(escape($config->getConfig('school')['url'])); ?>" >
                    <button class="btn btn-primary" onclick="PrintElem('#timeTable', '<?php echo escape(getActiveSpeechdayText()); ?>')">
                        <span class='glyphicon glyphicon-print'></span>&nbsp;&nbsp;Zeitplan ausdrucken
                    </button>

                    <form method="post" action="controller.php?action=downloadICS" class="inline-form" >
                        <button type='submit' class='btn btn-primary btn-ics-download' id='btn-ics-download' >
                            <span class='glyphicon glyphicon-calendar'></span>&nbsp;&nbsp;Zeitplan als ics Datei
                        </button>
                    </form>

                    <div id='timeTable' class="section-to-print"></div>
                </div>
            </div>
        </div>
-->
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h4 class='panel-title'>
                    <a data-toggle='collapse' data-parent='#accordion' href='#attendancemanagement'>
                        Anwesenheit
                    </a>
                </h4>
            </div>
            <div id='attendancemanagement' class='panel-collapse collapse in'>
                <div class='panel-body'>

                    <h4>
                        Aktuelle Anwesenheit
                    </h4>
                    <p id='attendance'>
                        <?php
                        $viewController = ViewController::getInstance();
                        $attendance = $viewController->action_attendance();
                        ?>
                    </p>

                    <?php if ($attendance != null && isBookingtimeForActiveSpeechday()): ?>
                    <h4>
                        Anwesenheit ändern
                    </h4>
                    <form id='changeAttendanceForm'>
                        <input type='hidden' name='userId' value='<?php echo(escape($user->getId())) ?>'>
                        <input type='hidden' name='eventId' value='<?php echo(escape($attendance['eventId'])) ?>'>
                        <div class='form-group'>
                            <label for='inputFromTime'>Von</label>
                            <select class='form-control' id='inputSlotDuration' name='inputFromTime'>
                                <?php echo(getDateOptions($attendance, true)); ?>
                            </select>
                        </div>

                        <div class='form-group'>
                            <label for='inputToTime'>Bis</label>
                            <select class='form-control' id='inputSlotDuration' name='inputToTime'>
                                <?php echo(getDateOptions($attendance, false)); ?>
                            </select>
                        </div>

                        <div class='form-group'>
                            <label for='inputAbsent'>Abwesend</label>
                            <input type='checkBox' class='form-control' id='inputAbsent' name='absent' placeholder='abwesend'>
                        </div>

                        <button type='submit' class='btn btn-primary' id='btn-change-attendance'>
                            Anwesenheit ändern
                        </button>
                    </form>

                    <div class='message-attendance' id='message-attendance'></div>
                    <?php else: ?>
                        <h2>
                            Die Anmeldungen laufen bereits!
                        </h2>
                        <p>
                        Um zu vermeiden, dass gebuchte Termine "gelöscht" werden, ist eine Änderung nicht mehr möglich.
                        </p><p>
                        Dies kann nur noch durch den Administrator erfolgen.
                        </p><p>
                        Als "Workaround" können Sie nicht gebuchte Termine als "Pause" markieren.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h4 class='panel-title'>
                    <a data-toggle='collapse' data-parent='#accordion' href='#pausemanagement'>
                        Pausen
                    </a>
                </h4>
            </div>
            <div id='pausemanagement' class='panel-collapse collapse'>
                <div class='panel-body'>
                    <div id='SlotsTablePauses'></div>
                </div>
            </div>
        </div>
        
        
        <div class='panel panel-default'>
            <div class='panel-heading'>
                <h4 class='panel-title'>
                    <a data-toggle='collapse' data-parent='#accordion' href='#roommanagement'>
                        Raum
                    </a>
                </h4>
            </div>
            <div id='roommanagement' class='panel-collapse collapse'>
                <div class='panel-body'>
                
                    <div id='RoomEditForm'></div>
                    
                    <div class='message-room' id='message-room'></div>
                    
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php include_once 'inc/footer.php'; ?>