$(document).ready(function () {
    loadTimeTable(1);
    loadSlots();
    loadRoom();
    
    $('#selectType').change(function () {
        var typeSelect = $('#selectType').find('option:selected');
        var typeId = typeSelect.val();

        loadTimeTable(typeId);
    });
});

$(document).on('click', '#btn-change-attendance', function () {
    $('#changeAttendanceForm').submit(function (e) {
        e.preventDefault();
        $('#changeAttendanceForm').unbind('submit');
        
        
        if (document.getElementById('inputAbsent').checked) {
            if (!confirm('WARNUNG!\n\nDies löscht alle eventuell gebuchten Einheiten und den gesetzten Raum!')) {
                return;
            }
        } else {
            if (!confirm('WARNUNG!\n\nDies löscht eventuell gebuchten Einheiten!')) {
                return;
            }
        }
        
        var postData = $(this).serializeArray();
        postData = postData.concat({name: 'action', value: 'changeAttendance'});

        var formURL = 'controller.php';
        $.ajax({
            url: formURL,
            type: 'POST',
            data: postData,
            success: function (data, textStatus, jqXHR) {
                var message = $('#message-attendance');
                if (data.indexOf('success') > -1) {
                    $('#attendance').load('viewController.php?action=attendance');

                    showMessage(message, 'success', 'Die Anwesenheit wurde erfolgreich geändert!');
                    loadSlots();
                    loadRoom();
                } else {
                    showMessage(message, 'danger', 'Die Anwesenheit konnte nicht geändert werden!');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showMessage(message, 'danger', 'Die Anwesenheit konnte nicht geändert werden!');
            }
        });
    });
    return true;
});


$(document).on('click', '#btn-change-room', function (event) {
    $('#changeRoomForm').submit(function (e) {
        e.preventDefault();
        $('#changeRoomForm').unbind('submit');
        
        var postData = $(this).serializeArray();
        postData = postData.concat({name: 'action', value: 'changeRoom'});

        var formURL = 'controller.php';
        $.ajax({
            url: formURL,
            type: 'POST',
            data: postData,
            success: function (data, textStatus, jqXHR) {
                var message = $('#message-room');
                if (data.indexOf('success') > -1) {
                    $('#room').load('viewController.php?action=room');
                    $('#SelectRoomId').load('viewController.php?action=getFreeRoomOptions');
                    
                    // $.get('viewController.php?action=setCurrentRoom', function(data){
                        // $('input[name="roomIdOld"]').val(data);
                    // });
                    
                    showMessage(message, 'success', 'Der Raum wurde erfolgreich geändert!');
                } else if (data.indexOf('RoomIsBlocked') > -1) {
                    showMessage(message, 'warning', 'Bitte wähle einen anderen Raum aus! Der Raum ist zwischenzeitlich bereits vergeben.');
                } else if (data.indexOf('NoOrSameRoom') > -1) {
                    showMessage(message, 'warning', 'Bitte wähle einen Raum aus!');    
                } else {
                    showMessage(message, 'danger', 'Der Raum konnte nicht geändert werden!');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showMessage(message, 'danger', 'Der Raum konnte nicht geändert werden!');
            }
        });
    });
    return true;
});

function loadTimeTable(typeId) {
    var timeTable = $('#timeTable');
    $.ajax({
        url: 'viewController.php?action=getTeacherTimeTable&typeId=' + typeId,
        dataType: 'html',
        type: 'GET',
        success: function (data, textStatus, jqXHR) {
            timeTable.html(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            timeTable.html('<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>');
        }
    });
}

function loadSlots() {
    var SlotsTablePauses = $('#SlotsTablePauses');
    $.ajax({
        url: 'viewController.php?action=getPausesSlots',
        dataType: 'html',
        type: 'GET',
        success: function (data, textStatus, jqXHR) {
            SlotsTablePauses.html(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            SlotsTablePauses.html('<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>');
        }
    });
}

function loadRoom() {
    var RoomEditForm = $('#RoomEditForm');
    $.ajax({
        url: 'viewController.php?action=getRoomEdit',
        dataType: 'html',
        type: 'GET',
        success: function (data, textStatus, jqXHR) {
            RoomEditForm.html(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            RoomEditForm.html('<h3>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>');
        }
    });
}

$(document).on('click', '.btn-pause', function (event) {
    var postData = $.parseJSON(this.value);
    var errorText = '<h3>Beim Laden der Termine ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>';
    postData.action = 'ToggleSlot';

    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: postData,
        success: function (data, textStatus, jqXHR) {
            if (data.indexOf('success') > -1) {
                loadSlots();
            } else if (data.indexOf('dirtyRead') > -1) {
                loadSlots();
                alert("WARNUNG!\n\nDer gewünschte Termin wurde in der Zwischenzeit vergeben! Bitte wählen Sie einen anderen Termin!");
            } else {
                $('#timeTable').html(errorText);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $('#timeTable').html(errorText);
        }
    });
});
