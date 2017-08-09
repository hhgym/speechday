function setTab() {
    var pageNameElem = $('#pageName');
    if (pageNameElem.length > 0) {
        var pageName = pageNameElem[0].innerText;
        var tabName = '#navTab' + pageName;
        $(tabName).addClass('active');
    }
}

function showMessage(element, type, message) {
    element.html('<div class="alert alert-' + type + '">' + message + '</div>');
    element.show();
    element.delay(5000).fadeOut(400);
}

function PrintElem(elem, pageTitle) {
    var data = $(elem).html();

    var printWindow = window.open('www.dachsberg.at', pageTitle, '');
    var doc = printWindow.document;
    doc.write("<html><head><title>" + pageTitle + "</title>");
    doc.write("<link href='css/print.css' rel='stylesheet' type='text/css' media='all' />");
    doc.write("</head><body>");
    doc.write(data);
    doc.write("</body></html>");
    doc.close();

    // function show() {
        // if (doc.readyState === "complete") {
            // printWindow.document.close();
            // printWindow.focus();
            // printWindow.print();
            // printWindow.close();
        // } else {
            // setTimeout(show, 100);
        // }
    // }

    // show();
    setTimeout(function(){
        printWindow.print();
        printWindow.close();
        },
        100
    );
    
}

// Backup print function

// function PrintElem(elem, pageTitle) {
    // var DocumentContainer = document.getElementById(elem);
    // var WindowObject = window.open('www.hhgym.de', pageTitle, 'width=750,height=650,top=50,left=50,toolbars=no,scrollbars=yes,status=no,resizable=yes');

    // WindowObject.document.writeln('<!DOCTYPE html>');
    // WindowObject.document.writeln('<html><head><title></title>');
    // WindowObject.document.writeln('<link rel="stylesheet" type="text/css" href="css/print.css" media="all">');
    // WindowObject.document.writeln('</head><body>')
    
    // WindowObject.document.writeln(DocumentContainer.innerHTML);
    // WindowObject.document.writeln('</body></html>');

    // WindowObject.document.close();
    // WindowObject.focus();
    // setTimeout(function(){WindowObject.print();WindowObject.close();},100);
    // WindowObject.print();
    // WindowObject.close();
// }