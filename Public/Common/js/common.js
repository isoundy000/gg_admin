/**
 * Created by Cherish on 2016/12/22.
 */

function ajaxRequest(url, data, type, dataType, success) {
    $.ajax( {
        url : url,
        type : type,
        data : data,
        dataType : dataType,
        beforeSend : function() {
            beforeSend('visible');
        },
        success : function(res) {
            beforeSend('hidden');
            success && typeof success=='function' && success.call(null, res);
        },
        error : function(XMLHttpRequest, textStatus, errorThrown) {
            var message = XMLHttpRequest.responseJSON.msg ? XMLHttpRequest.responseJSON.msg : textStatus + " " + errorThrown;
            errorDialog("block", XMLHttpRequest.status, message);
        }
    });
}

function beforeSend(style) {

}

function errorDialog(display, title, message) {
    var html = '<div class="modal-dialog">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="errorDialog(' + "'none'" + ')">' +
        '<span aria-hidden="true">×</span></button>' +
    '<h4 class="modal-title">' + title + '</h4>' +
    '</div>' +
    '<div class="modal-body">' +
        '<p>' + message + '</p>' +
    '</div>' +
    '<div class="modal-footer">' +
        '<button type="button" class="btn btn-outline" onclick="errorDialog(' + "'none'" + ')">关闭</button>' +
    '</div>' +
    '</div>' +
    '</div>';
    $("#error-dialog").html(html);
    $("#error-dialog").css("display", display);
}

function successDialog(display, title, message) {
    var html = '<div class="modal-dialog">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="errorDialog(' + "'none'" + ')">' +
        '<span aria-hidden="true">×</span></button>' +
        '<h4 class="modal-title">' + title + '</h4>' +
        '</div>' +
        '<div class="modal-body">' +
        '<p>' + message + '</p>' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-outline" onclick="errorDialog(' + "'none'" + ')">关闭</button>' +
        '</div>' +
        '</div>' +
        '</div>';
    $("#success-dialog").html(html);
    $("#success-dialog").css("display", display);
}