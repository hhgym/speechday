$(document).ready(function () {
    loadTimeTable(1);
    loadSlots();
    
    $('#selectType').change(function () {
        var typeSelect = $('#selectType').find('option:selected');
        var typeId = typeSelect.val();

        loadTimeTable(typeId);
    });
});

$(document).on('click', '#btn-change-attendance', function () {
    $('#changeAttendanceForm').submit(function (e) {
        var postData = $(this).serializeArray();
        postData = postData.concat({name: 'action', value: 'changeAttendance'});

        var formURL = 'controller.php';
        $.ajax({
            url: formURL,
            type: 'POST',
            data: postData,
            success: function (data, textStatus, jqXHR) {
                var message = $('#message');
                if (data.indexOf('success') > -1) {
                    $('#attendance').load('viewController.php?action=attendance');

                    showMessage(message, 'success', 'Die Anwesenheit wurde erfolgreich geändert!');
                    loadSlots();
                } else {
                    showMessage(message, 'danger', 'Die Anwesenheit konnte nicht geändert werden!');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showMessage(message, 'danger', 'Die Anwesenheit konnte nicht geändert werden!');
            }
        });
        e.preventDefault();
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
