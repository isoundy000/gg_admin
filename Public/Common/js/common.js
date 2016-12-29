/**
 * Created by Cherish on 2016/12/22.
 */

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
            try {
                var response = JSON.parse(XMLHttpRequest.responseText);
                var message = response.msg ? response.msg : textStatus + " " + errorThrown;
                errorDialog("block", XMLHttpRequest.status, message);
            } catch (e) {
                var message = "该功能未实现";
                if(XMLHttpRequest.status!=200 && XMLHttpRequest.status!=201) {
                    errorDialog("block", XMLHttpRequest.status, message);
                } else {
                    console.log("返回非JSON格式数据");
                }
            }
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

function warningDialog(display, title, message, event) {
    var html = '<div class="modal-dialog">' +
        '<div class="modal-content">' +
        '<div class="modal-header">' +
        '<button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="warningDialog(' + "'none'" + ')">' +
        '<span aria-hidden="true">×</span></button>' +
        '<h4 class="modal-title">' + title + '</h4>' +
        '</div>' +
        '<div class="modal-body">' +
        '<p>' + message + '</p>' +
        '</div>' +
        '<div class="modal-footer">' +
        '<button type="button" class="btn btn-outline pull-left" onclick="warningDialog(' + "'none'" + ')">关闭</button>' +
        '<button type="button" class="btn btn-outline" onclick="' + event + '">确定</button>' +
        '</div>' +
        '</div>' +
        '</div>';
    $("#warning-dialog").html(html);
    $("#warning-dialog").css("display", display);
}