/**
 * Created by Cherish on 2016/12/22.
 */

function ajaxRequest(url, data, type, dataType, success, error) {
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
            beforSend('hidden');
        }
    });
}

function beforeSend(style) {

}