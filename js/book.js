$(document).ready(function () {

    $('#selectTeacher').change(function () {
        var teacherSelect = $('#selectTeacher');
        teacherSelect.find("option[value='-1']").remove();

        teacherSelect = teacherSelect.find('option:selected');
        var teacherId = teacherSelect.val();

        loadTimeTable(teacherId);
    });

});

$(document).ready(function () {

    $('#selectStudent').change(function () {
        var studentSelect = $('#selectStudent');
        studentSelect.find("option[value='-1']").remove();

        studentSelect = studentSelect.find('option:selected');
        var studentId = studentSelect.val();

        loadTimeTable(studentId);
    });

});


$(document).on('click', '.btn-book', function (event) {
    // userId is the ID of the user who's slot are booked
    var postData = $.parseJSON(this.value);
    var userId = postData.userId;
    var errorText = '<h3>Beim Laden der Termine ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h3>';
    postData.action = 'changeSlot';

    $.ajax({
        url: 'controller.php',
        type: 'POST',
        data: postData,
        success: function (data, textStatus, jqXHR) {
            if (data.indexOf('success') > -1) {
                loadTimeTable(userId);
            } else if (data.indexOf('dirtyRead') > -1) {
                loadTimeTable(userId);
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

function loadTimeTable(userId) {
    var timeTable = $('#timeTable');
    $.ajax({
        url: 'viewController.php?action=getTimeTable&userId=' + userId,
        dataType: 'html',
        type: 'GET',
        success: function (data, textStatus, jqXHR) {
            timeTable.html(data);
        },
        error: function (jqXHR, textStatus, errorThrown) {
            timeTable.html('<h1>Es ist ein Fehler aufgetreten!<br>Bitte versuche es später erneut!</h1>');
        }
    });
}