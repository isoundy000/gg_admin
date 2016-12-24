/**
 * Created by Cherish on 2016/12/22.
 */

function logout(_url) {
    ajaxRequest(_url, null, 'delete', 'json', function(result) {
        if (!result) {
            window.location.href = "/";
        }
    });
}

function menuClick(_url) {
    ajaxRequest(_url, null, 'get', 'json', function(result) {
        if(result.code == 200) {
            $("#content-wrapper").html(result.data.html);
        }
    });
}

function ajaxRequest(url, data, type, dataType, success) {
    $.ajax( {
        url : url,
        type : type,
        data : data,
        dataType : dataType,
        success : function(res) {
            success && typeof success=='function' && success.call(null, res);
        },
        error : function(XMLHttpRequest, textStatus, errorThrown) {
            var message = textStatus + " " + errorThrown;
            errorDialog("block", XMLHttpRequest.status, message);
        }
    });
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
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="successDialog(' + "'none'" + ')">' +
        '<span aria-hidden="true">×</span></button>' +
        '<h4 class="modal-title">' + title + '</h4>' +
        '</div>' +
        '<div class="modal-body">' +
        '<p>' + message + '</p>' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-outline" onclick="successDialog(' + "'none'" + ')">关闭</button>' +
        '</div>' +
        '</div>' +
        '</div>';
    $("#success-dialog").html(html);
    $("#success-dialog").css("display", display);
}