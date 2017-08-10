function validateForm() {
    $('#changeUsersForm').validate({
        rules: {
            password: {
                minlength: 8,
                required: true
            }
        },
        messages: {
            password: 'Gib ein Passwort mit mindestens 8 Zeichen ein!',
        },
        highlight: function (element) {
            var id_attr = '#' + $(element).attr('id') + '1';
            $(element).closest('.form-group').removeClass('has-success').addClass('has-error');
            $(id_attr).removeClass('glyphicon-ok').addClass('glyphicon-remove');
        },
        unhighlight: function (element) {
            var id_attr = '#' + $(element).attr('id') + '1';
            $(element).closest('.form-group').removeClass('has-error').addClass('has-success');
            $(id_attr).removeClass('glyphicon-remove').addClass('glyphicon-ok');
        },
        errorElement: 'span',
        errorClass: 'help-block',
        errorPlacement: function (error, element) {
            if (element.length) {
                error.insertAfter(element);
            } else {
                error.insertAfter(element);
            }
        }
    });
}


$(document).on('click', '#btn-edit-user-pwd', function (event) {
    validateForm();
    var editUsersForm = $('#changeUsersForm');
    editUsersForm.submit(function (e) {
        if (editUsersForm.valid()) {
            var postData = $(this).serializeArray();
            postData = postData.concat({name: 'action', value: 'editUserPwd'});
            var message = $('#changePasswortChangeMessage');

            var formURL = 'controller.php';
            $.ajax({
                url: formURL,
                type: 'POST',
                data: postData,
                success: function (data, textStatus, jqXHR) {
                    if (data.indexOf('success') > -1) {
                        showMessage(message, 'success', 'Das Passwort wurde erfolgreich geändert!');
                    } else {
                        showMessage(message, 'danger', 'Das Passwort  konnte nicht geändert werden!');
                    }
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    showMessage(message, 'danger', 'Das Passwort  konnte nicht geändert werden!');
                }
            });
            e.preventDefault();
            // editUsersForm.unbind('submit');
        }
    });
});
