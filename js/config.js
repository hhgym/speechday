$(document).on('click', '#btn-edit-config', function (event) {
    var changeEventForm = $('#changeConfigForm');
    changeEventForm.submit(function (e) {
        var postData = $(this).serializeArray();
        postData = postData.concat({name: 'action', value: 'changeConfig'});
        var message = $('#changeConfigFormMessage');

        var formURL = 'controller.php';
        $.ajax({
            url: formURL,
            type: 'POST',
            data: postData,
            success: function (data, textStatus, jqXHR) {
                if (data.indexOf('success') > -1) {
                    showMessage(message, 'success', 'Die Konfiguration wurde erfolgreich gespeichert!');
                    displayActiveEvent();
                } else {
                    showMessage(message, 'danger', 'Die Konfiguration konnte nicht gespeichert werden!');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                showMessage(message, 'danger', 'Die Konfiguration konnte nicht gespeichert werden!');
            }
        });
        e.preventDefault();
        changeEventForm.unbind('submit');
    });
});